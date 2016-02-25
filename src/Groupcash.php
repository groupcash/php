<?php
namespace groupcash\php;

use groupcash\php\model\Coin;
use groupcash\php\model\Base;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Transaction;

class Groupcash {

    /** @var KeyService */
    private $key;

    /** @var Finger */
    private $finger;

    /**
     * @param KeyService $key
     * @param Finger $finger
     */
    public function __construct(KeyService $key, Finger $finger) {
        $this->key = $key;
        $this->finger = $finger;
    }

    public function issueCoin($issuerKey, Promise $promise, Output $output) {
        $signer = new Signer($this->key, $this->finger, $issuerKey);

        return new Coin(new Base($promise, $output, $signer->sign([[$promise], [$output]])), 0);
    }

    /**
     * @param string $ownerKey
     * @param Coin[] $coins
     * @param Output[] $outputs
     * @return model\Coin[]
     * @throws \Exception
     */
    public function transferCoins($ownerKey, array $coins, array $outputs) {
        if (!$coins) {
            throw new \Exception('No coins given.');
        }
        if (!$outputs) {
            throw new \Exception('No outputs given.');
        }

        $outputValue = array_reduce($outputs, function (Fraction $sum, Output $output) {
            if ($output->getValue()->isLessThan(new Fraction(0)) || $output->getValue() == new Fraction(0)) {
                throw new \Exception('Output values must be positive.');
            }

            return $sum->plus($output->getValue());
        }, new Fraction(0));

        $inputValue = array_reduce($coins, function (Fraction $sum, Input $input) {
            return $sum->plus($input->getOutput()->getValue());
        }, new Fraction(0));

        if ($inputValue != $outputValue) {
            throw new \Exception('The output value must equal the input value.');
        }

        $owners = array_unique(array_map(function (Coin $coin) {
            return $coin->getOwner();
        }, $coins));

        if (count($owners) != 1) {
            throw new \Exception('All coins must have the same owner.');
        }
        if ($owners[0] != $this->key->publicKey($ownerKey)) {
            throw new \Exception('Only the owner can transfer coins.');
        }

        $signer = new Signer($this->key, $this->finger, $ownerKey);
        $transaction = new Transaction($coins, $outputs, $signer->sign([$coins, $outputs]));

        $coins = [];
        foreach ($outputs as $i => $output) {
            $coins[] = new Coin($transaction, $i);
        }
        return $coins;
    }
}