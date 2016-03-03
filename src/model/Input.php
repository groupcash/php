<?php
namespace groupcash\php\model;

use groupcash\php\model\signing\Finger;

/**
 * An Input references one Output of another Transaction.
 */
class Input implements Finger {

    /** @var Transaction */
    private $transaction;

    /** @var int */
    private $outputIndex;

    /**
     * @param Transaction $transaction
     * @param int $outputIndex
     */
    public function __construct(Transaction $transaction, $outputIndex) {
        $this->transaction = $transaction;
        $this->outputIndex = $outputIndex;
    }

    /**
     * @return Transaction
     */
    public function getTransaction() {
        return $this->transaction;
    }

    /**
     * @return Output
     */
    public function getOutput() {
        return $this->transaction->getOutputs()[$this->outputIndex];
    }

    /**
     * @return int
     */
    public function getOutputIndex() {
        return $this->outputIndex;
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return [$this->transaction, $this->outputIndex];
    }
}