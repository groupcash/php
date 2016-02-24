<?php
namespace groupcash\php\model;

/**
 * A Transaction distributes its Inputs to its Outputs, signed by the target of its inputs.
 */
class Transaction {

    /** @var Input[] */
    private $inputs = [];

    /** @var Output[] */
    private $outputs = [];

    /** @var Signature */
    private $signature;

    /**
     * @param Input[] $inputs
     * @param Output[] $outputs
     * @param Signature $signature
     */
    public function __construct(array $inputs, array $outputs, Signature $signature) {
        $this->inputs = $inputs;
        $this->outputs = $outputs;
        $this->signature = $signature;
    }

    /**
     * @return Input[]
     */
    public function getInputs() {
        return $this->inputs;
    }

    /**
     * @return Output[]
     */
    public function getOutputs() {
        return $this->outputs;
    }

    /**
     * @return Signature
     */
    public function getSignature() {
        return $this->signature;
    }
}