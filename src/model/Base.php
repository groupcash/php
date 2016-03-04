<?php
namespace groupcash\php\model;

use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Signer;

/**
 * The first Transaction of a Coin defining its backing and currency.
 *
 * The currency, description and a single output with a backer as target is signed by an issuer.
 */
class Base extends Transaction {

    /** @var Binary */
    private $issuerAddress;

    /** @var string */
    private $description;

    /** @var Binary */
    private $currency;

    /**
     * @param Binary $currency
     * @param string $description
     * @param Output $output
     * @param Binary $issuerAddress
     * @param string $signature
     */
    public function __construct(Binary $currency, $description, Output $output, Binary $issuerAddress, $signature) {
        parent::__construct([], [$output], $signature);
        $this->description = $description;
        $this->currency = $currency;
        $this->issuerAddress = $issuerAddress;
    }

    /**
     * @param string $description
     * @param Binary $currency
     * @param Output $output
     * @param Signer $signer
     * @return Base
     */
    public static function signedBase($description, Binary $currency, Output $output, Signer $signer) {
        return new Base($currency, $description, $output, $signer->getAddress(),
            $signer->sign([$currency, $description, $output]));
    }

    /**
     * @return array
     */
    public function getPrint() {
        return [$this->currency, $this->description, $this->getOutput()];
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return Binary
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @return Output
     */
    public function getOutput() {
        return $this->getOutputs()[0];
    }

    /**
     * @return Binary
     */
    public function getIssuerAddress() {
        return $this->issuerAddress;
    }
}