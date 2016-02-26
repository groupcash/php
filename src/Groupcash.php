<?php
namespace groupcash\php;

use groupcash\php\model\Coin;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Signer;

class Groupcash {

    /** @var KeyService */
    private $key;

    /**
     * @param KeyService $key
     */
    public function __construct(KeyService $key) {
        $this->key = $key;
    }

    /**
     * Generates a new private key.
     *
     * @return string
     */
    public function generateKey() {
        return $this->key->generatePrivateKey();
    }

    /**
     * Displays the public key corresponding to the given private key.
     *
     * @param string $key
     * @return string
     */
    public function getAddress($key) {
        return $this->key->publicKey($key);
    }

    /**
     * Creates a new coin based on a delivery promise.
     *
     * @param $issuerKey
     * @param Promise $promise
     * @param Output $output
     * @return Coin
     */
    public function issueCoin($issuerKey, Promise $promise, Output $output) {
        return Coin::issue($promise, $output, new Signer($this->key, $issuerKey));
    }

    /**
     * Transfers the values of one or more coins to one or more targets.
     *
     * @param string $ownerKey
     * @param Coin[] $coins
     * @param Output[] $outputs
     * @return model\Coin[]
     * @throws \Exception
     */
    public function transferCoins($ownerKey, array $coins, array $outputs) {
        $inputs = array_map(function (Coin $coin) {
            return $coin->getInput();
        }, $coins);

        $transferred = Coin::transfer($inputs, $outputs, new Signer($this->key, $ownerKey));

        foreach ($transferred as $coin) {
            $this->verifyCoin($coin);
        }
        return $transferred;
    }

    /**
     * Creates a new coin with a value proportional to the bases of the backer.
     *
     * @param string $backerKey
     * @param Coin $coin
     * @return Coin
     * @throws \Exception
     */
    public function confirmCoin($backerKey, Coin $coin) {
        $backer = $this->key->publicKey($backerKey);
        $confirmed = $coin->confirm($backer, new Signer($this->key, $backerKey), $this->key);

        $this->verifyCoin($confirmed);
        return $confirmed;
    }

    /**
     * Verifies that the Coin is internally consistent.
     *
     * @param Coin $coin
     * @throw Exception if Coin does not verify
     */
    private function verifyCoin(Coin $coin) {
        (new Verification($coin))->mustBeOk();
    }
}