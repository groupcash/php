<?php
namespace groupcash\php\model;

class Confirmation extends Transaction {

    /** @var string */
    private $fingerprint;

    /**
     * @param Base[] $bases
     * @param Output $output
     * @param string $fingerprint
     * @param Signature $signature
     */
    public function __construct(array $bases, $output, $fingerprint, Signature $signature) {
        parent::__construct(array_map([$this, 'makeInput'], $bases), [$output], $signature);
        $this->fingerprint = $fingerprint;
    }

    /**
     * @return Base[]
     */
    public function getBases() {
        return array_map(function (Input $input) {
            return $input->getTransaction();
        }, $this->getInputs());
    }

    /**
     * @return Output
     */
    public function getOutput() {
        return $this->getOutputs()[0];
    }

    private function makeInput(Base $base) {
        return new Input($base, 0);
    }

    /**
     * @return string
     */
    public function getFingerprint() {
        return $this->fingerprint;
    }
}