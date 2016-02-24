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
     * @param string $version
     * @param Transaction $transaction
     * @param $outputIndex
     */
    public function __construct($version, Transaction $transaction, $outputIndex) {
        parent::__construct($transaction, $outputIndex);

        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }
}