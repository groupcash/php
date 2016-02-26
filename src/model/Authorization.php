<?php
namespace groupcash\php\model;

class Authorization implements Finger{

    /** @var string */
    private $issuerAddress;

    /** @var Signature */
    private $signature;

    /**
     * @param string $issuerAddress
     * @param Signer $signer
     * @return Authorization
     */
    public static function signed($issuerAddress, Signer $signer) {
        return new Authorization($issuerAddress, $signer->sign($issuerAddress));
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return $this->issuerAddress;
    }

    /**
     * @param string $issuerAddress
     * @param Signature $signature
     */
    public function __construct($issuerAddress, Signature $signature) {
        $this->issuerAddress = $issuerAddress;
        $this->signature = $signature;
    }

    /**
     * @return Signature
     */
    public function getSignature() {
        return $this->signature;
    }

    /**
     * @param string $issuerAddress
     * @param string $currencyAddress
     * @return bool
     */
    public function authorizes($issuerAddress, $currencyAddress) {
        return $this->issuerAddress == $issuerAddress && $currencyAddress == $this->signature->getSigner();
    }
}