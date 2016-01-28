<?php
namespace groupcash\php\model;

class Coin {

    /** @var Transaction */
    private $transaction;

    /** @var Signature */
    private $signature;

    /**
     * @param Transaction $transaction
     * @param Signature $signature
     */
    private function __construct(Transaction $transaction, Signature $signature) {
        $this->transaction = $transaction;
        $this->signature = $signature;
    }

    private static function create(Transaction $transaction, Signer $signer) {
        return new Coin($transaction, $signer->sign($transaction));
    }

    public static function issue(Promise $promise, Signer $issuer) {
        return self::create($promise, $issuer);
    }

    public function transfer($newOwnerAddress, Signer $owner, $prev = null) {
        return self::create(new Transference($this, $newOwnerAddress, $prev), $owner);
    }

    /**
     * @return Transaction
     */
    public function getTransaction() {
        return $this->transaction;
    }

    /**
     * @return Signature
     */
    public function getSignature() {
        return $this->signature;
    }

    function __toString() {
        return (string)$this->transaction . ', ' . $this->signature->getSigner();
    }
}