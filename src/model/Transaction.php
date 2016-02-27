<?php
namespace groupcash\php\model;

/**
 * A Transaction distributes its Inputs to its Outputs, signed by the target of its inputs.
 */
class Transaction implements Finger {

    /** @var Input[] */
    private $inputs = [];

    /** @var Output[] */
    private $outputs = [];

    /** @var string */
    private $signature;

    /**
     * @param Input[] $inputs
     * @param Output[] $outputs
     * @param string $signature
     */
    public function __construct(array $inputs, array $outputs, $signature) {
        $this->inputs = $inputs;
        $this->outputs = $outputs;
        $this->signature = $signature;
    }

    /**
     * @param Input[] $inputs
     * @param Output[] $outputs
     * @param Signer $signer
     * @return Transaction
     */
    public static function signedTransaction($inputs, $outputs, Signer $signer) {
        return new Transaction($inputs, $outputs, $signer->sign([$inputs, $outputs]));
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return [$this->inputs, $this->outputs];
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
     * @return string
     */
    public function getSignature() {
        return $this->signature;
    }
}