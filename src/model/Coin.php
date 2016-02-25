<?php
namespace groupcash\php\model;

use groupcash\php\Signer;

/**
 * A Coin is a tree of Transactions with Promises at its leafs.
 */
class Coin extends Input {

    const VERSION = '1.0';

    /** @var string */
    private $version;

    /**
     * @param Transaction $transaction
     * @param $outputIndex
     */
    public function __construct(Transaction $transaction, $outputIndex) {
        parent::__construct($transaction, $outputIndex);

        $this->version = self::VERSION;
    }

    /**
     * @param Promise $promise
     * @param Output $output
     * @param Signer $signer
     * @return Coin
     */
    public static function issue(Promise $promise, Output $output, Signer $signer) {
        return new Coin(new Base($promise, $output, $signer->sign([[$promise], [$output]])), 0);
    }

    /**
     * @param Coin[] $coins
     * @param Output[] $outputs
     * @param Signer $signer
     * @return Coin[]
     */
    public static function transfer($coins, $outputs, Signer $signer) {
        $transaction = new Transaction($coins, $outputs, $signer->sign([$coins, $outputs]));

        $coins = [];
        foreach ($outputs as $i => $output) {
            $coins[] = new Coin($transaction, $i);
        }
        return $coins;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getOwner() {
        return $this->getOutput()->getTarget();
    }

    /**
     * @return Fraction
     */
    public function getValue() {
        return $this->getOutput()->getValue();
    }
}