<?php
namespace groupcash\php\model;

use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Finger;
use groupcash\php\model\signing\Signer;

class RuleBook implements Finger {

    /** @var Binary */
    private $currencyAddress;

    /** @var string */
    private $rules;

    /** @var Binary|null */
    private $previousHash;

    /** @var string */
    private $signature;

    /**
     * @param Binary $currencyAddress
     * @param string $rules
     * @param string $signature
     * @param Binary|null $previousHash
     */
    public function __construct(Binary $currencyAddress, $rules, $signature, Binary $previousHash = null) {
        $this->currencyAddress = $currencyAddress;
        $this->rules = $rules;
        $this->previousHash = $previousHash;
        $this->signature = $signature;
    }

    /**
     * @param Signer $currency
     * @param Binary $address
     * @param string $rules
     * @param null|RuleBook $previous
     * @return RuleBook
     */
    public static function signed(Signer $currency, Binary $address, $rules, RuleBook $previous = null) {
        $previousHash = $previous ? $previous->hash() : null;

        return new RuleBook($address, $rules, $currency->sign([$address, $rules, $previousHash]), $previousHash);
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
     * @return Binary|null
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