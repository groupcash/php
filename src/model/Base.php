<?php
namespace groupcash\php\model;

/**
 * The first Transaction of a Coin.
 *
 * A Base is signed by an issuer, has a Promise as its only Input which is transferred to its backer.
 */
class Base extends Transaction {

    /** @var Promise */
    private $promise;

    /** @var string */
    private $issuerAddress;

    /**
     * @param Promise $promise
     * @param Output $output
     * @param string $signature
     * @param string $issuerAddress
     */
    public function __construct(Promise $promise, Output $output, $issuerAddress, $signature) {
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
     * @return string
     */
    public function getIssuerAddress() {
        return $this->issuerAddress;
    }
}