<?php
namespace groupcash\php\model;

use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Signer;
use groupcash\php\model\value\Fraction;

class Confirmation extends Transaction {

    /** @var Binary */
    private $hash;

    /**
     * @param Base[] $bases
     * @param Output $output
     * @param Binary $hash
     * @param string $signature
     */
    public function __construct(array $bases, Output $output, Binary $hash, $signature) {
        parent::__construct(
            array_map([$this, 'makeInput'], $bases),
            $this->keepChange($bases, $output),
            $signature);

        $this->hash = $hash;
    }

    /**
     * @param Base[] $bases
     * @param Output $output
     * @param Transaction $confirmed
     * @param Signer $signer
     * @return Confirmation
     */
    public static function signedConfirmation($bases, Output $output, Transaction $confirmed, Signer $signer) {
        $hash = self::hash(Signer::squash($confirmed));

        return new Confirmation($bases, $output, $hash,
            $signer->sign([$bases, $output, $hash]));
    }

    /**
     * @param string $content
     * @return Binary
     */
    public static function hash($content) {
        return new Binary(hash('sha256', $content, true));
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
     * @return Binary
     */
    public function getHash() {
        return $this->hash;
    }

    private function makeInput(Base $base) {
        return new Input($base, 0);
    }

    /**
     * @param Base[] $bases
     * @param Output $output
     * @return Output[]
     */
    private function keepChange(array $bases, Output $output) {
        /** @var Fraction $sum */
        $sum = array_reduce($bases, function (Fraction $sum, Base $base) {
            return $sum->plus($base->getOutput()->getValue());
        }, new Fraction(0));

        if ($sum == $output->getValue()) {
            return [$output];
        }

        return [
            $output,
            new Output(new Binary(null), $sum->minus($output->getValue()))
        ];
    }
}