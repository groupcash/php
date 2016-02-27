<?php
namespace groupcash\php\model;

class Authorization implements Finger{

    /** @var string */
    private $issuerAddress;

    /** @var string */
    private $signature;

    /** @var string */
    private $currencyAddress;

    /**
     * @param string $issuerAddress
     * @param string $currencyAddress
     * @param string $signature
     */
    public function __construct($issuerAddress, $currencyAddress, $signature) {
        $this->issuerAddress = $issuerAddress;
        $this->signature = $signature;
        $this->currencyAddress = $currencyAddress;
    }

    /**
     * @param string $issuerAddress
     * @param string $currencyAddress
     * @param Signer $signer
     * @return Authorization
     */
    public static function signed($issuerAddress, Signer $signer) {
        return new Authorization($issuerAddress, $signer->getAddress(), $signer->sign($issuerAddress));
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return $this->issuerAddress;
    }

    /**
     * @return string
     */
    public function getIssuerAddress() {
        return $this->issuerAddress;
    }

    /**
     * @return string
     */
    public function getSignature() {
        return $this->signature;
    }

    /**
     * @return string
     */
    public function getCurrencyAddress() {
        return $this->currencyAddress;
    }
}