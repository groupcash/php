<?php
namespace groupcash\php\model;

use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Finger;
use groupcash\php\model\signing\Signer;

class CurrencyRules implements Finger {

    /** @var Binary */
    private $currencyAddress;

    /** @var string */
    private $rules;

    /** @var Binary */
    private $previousHash;

    /** @var string */
    private $signature;

    /**
     * @param Binary $currencyAddress
     * @param string $rules
     * @param Binary|null $previousHash
     * @param string $signature
     */
    public function __construct(Binary $currencyAddress, $rules, $previousHash, $signature) {
        $this->currencyAddress = $currencyAddress;
        $this->rules = $rules;
        $this->previousHash = $previousHash;
        $this->signature = $signature;
    }

    /**
     * @param Signer $currency
     * @param Binary $address
     * @param string $rules
     * @param null|CurrencyRules $previous
     * @return CurrencyRules
     */
    public static function sign(Signer $currency, Binary $address, $rules, CurrencyRules $previous = null) {
        $previousHash = $previous ? $previous->hash() : null;

        return new CurrencyRules($address, $rules, $previousHash,
            $currency->sign([$address, $rules, $previousHash]));
    }

    /**
     * @return array
     */
    public function getPrint() {
        return [$this->currencyAddress, $this->rules, $this->previousHash];
    }

    /**
     * @return Binary
     */
    public function hash() {
        return new Binary(hash('sha256', Signer::squash($this), true));
    }

    /**
     * @return Binary
     */
    public function getCurrencyAddress() {
        return $this->currencyAddress;
    }

    /**
     * @return string
     */
    public function getRules() {
        return $this->rules;
    }

    /**
     * @return Binary
     */
    public function getPreviousHash() {
        return $this->previousHash;
    }

    /**
     * @return string
     */
    public function getSignature() {
        return $this->signature;
    }
}