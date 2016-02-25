<?php
namespace groupcash\php\model;

/**
 * A Coin is a tree of Transactions with Promises at its leafs.
 */
class Coin extends Input {

    const VERSION = '1.0';

    /** @var string */
    private $version;

    /**
     * @param Transaction $transaction
     * @param $outputIndex
     */
    public function __construct(Transaction $transaction, $outputIndex) {
        parent::__construct($transaction, $outputIndex);

        $this->version = self::VERSION;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getOwner() {
        return $this->getOutput()->getTarget();
    }

    /**
     * @return Fraction
     */
    public function getValue() {
        return $this->getOutput()->getValue();
    }
}