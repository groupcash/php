<?php
namespace groupcash\php\model;

/**
 * An Input references one Output of another Transaction.
 */
class Input {

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
}