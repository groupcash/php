<?php
namespace groupcash\php\model;
use groupcash\php\key\Binary;

/**
 * The first Transaction of a Coin.
 *
 * A Base is signed by an issuer, has a Promise as its only Input which is transferred to its backer.
 */
class Base extends Transaction {

    /** @var Promise */
    private $promise;

    /** @var Binary */
    private $issuerAddress;

    /**
     * @param Promise $promise
     * @param Output $output
     * @param string $signature
     * @param Binary $issuerAddress
     */
    public function __construct(Promise $promise, Output $output, Binary $issuerAddress, $signature) {
        parent::__construct([], [$output], $signature);
        $this->promise = $promise;
        $this->issuerAddress = $issuerAddress;
    }

    /**
     * @param Promise $promise
     * @param Output $output
     * @param Signer $signer
     * @return Base
     */
    public static function signedBase(Promise $promise, Output $output, Signer $signer) {
        return new Base($promise, $output, $signer->getAddress(), $signer->sign([$promise, $output]));
    }

    /**
     * @return array
     */
    public function getPrint() {
        return [$this->getPromise(), $this->getOutput()];
    }

    /**
     * @return Promise
     */
    public function getPromise() {
        return $this->promise;
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