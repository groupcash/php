<?php
namespace groupcash\php\model;
use groupcash\php\Signer;

/**
 * An Issue is the first Transaction of a Coin.
 *
 * It is signed by an issuer, has a Promise as its only Input which is transferred to its backer.
 */
class Issue extends Transaction {

    /** @var Promise */
    private $promise;

    /** @var Output */
    private $output;

    /**
     * @param Promise $promise
     * @param Output $output
     * @param Signature $signature
     */
    public function __construct(Promise $promise, Output $output, Signature $signature) {
        parent::__construct([$promise], [$output], $signature);
        $this->promise = $promise;
        $this->output = $output;
    }

    public static function coin(Promise $promise, Output $output, Signer $signer) {
        return new Coin(Coin::VERSION, new Issue($promise, $output, $signer->sign([[$promise], [$output]])), 0);
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
        return $this->output;
    }
}