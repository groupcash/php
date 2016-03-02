<?php
namespace groupcash\php\model;

use groupcash\php\key\Binary;

class Authorization implements Finger{

    /** @var Binary */
    private $issuerAddress;

    /** @var Binary */
    private $currencyAddress;

    /** @var string */
    private $signature;

    /**
     * @param Binary $issuerAddress
     * @param Binary $currencyAddress
     * @param string $signature
     */
    public function __construct(Binary $issuerAddress, Binary $currencyAddress, $signature) {
        $this->issuerAddress = $issuerAddress;
        $this->signature = $signature;
        $this->currencyAddress = $currencyAddress;
    }

    /**
     * @param Binary $issuerAddress
     * @param Signer $signer
     * @return Authorization
     */
    public static function signed(Binary $issuerAddress, Signer $signer) {
        return new Authorization($issuerAddress, $signer->getAddress(), $signer->sign($issuerAddress));
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return $this->issuerAddress;
    }

    /**
     * @return Binary
     */
    public function getIssuerAddress() {
        return $this->issuerAddress;
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
    public function getSignature() {
        return $this->signature;
    }
}