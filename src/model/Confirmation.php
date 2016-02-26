<?php
namespace groupcash\php\model;

class Confirmation extends Transaction {

    /** @var string */
    private $hash;

    /**
     * @param Base[] $bases
     * @param Output $output
     * @param string $hash
     * @param Signature $signature
     */
    public function __construct(array $bases, Output $output, $hash, Signature $signature) {
        parent::__construct(array_map([$this, 'makeInput'], $bases), [$output], $signature);
        $this->hash = $hash;
    }

    /**
     * @param Base[] $bases
     * @param Output $output
     * @param string $hash
     * @param Signer $signer
     * @return Confirmation
     */
    public static function signedConfirmation($bases, Output $output, $hash, Signer $signer) {
        return new Confirmation($bases, $output, $hash,
            $signer->sign([$bases, $output, $hash]));
    }

    /**
     * @return array
     */
    public function getPrint() {
        return [$this->getBases(), $this->getOutput(), $this->hash];
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

    /**
     * @return string
     */
    public function getHash() {
        return $this->hash;
    }

    private function makeInput(Base $base) {
        return new Input($base, 0);
    }
}