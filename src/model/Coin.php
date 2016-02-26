<?php
namespace groupcash\php\model;

use groupcash\php\KeyService;

/**
 * A Coin is a tree of Transactions with Promises at its leafs.
 */
class Coin {

    /** @var Input */
    private $input;

    /**
     * @param Input $input
     */
    public function __construct(Input $input) {
        $this->input = $input;
    }

    public function version() {
        return 'dev';
    }

    /**
     * @return Input
     */
    public function getInput() {
        return $this->input;
    }

    /**
     * @return string
     */
    public function getOwner() {
        return $this->input->getOutput()->getTarget();
    }

    /**
     * @return Fraction
     */
    public function getValue() {
        return $this->input->getOutput()->getValue();
    }

    /**
     * @return Base[]
     */
    public function getBases() {
        return $this->getBasesOf($this->input->getTransaction());
    }

    /**
     * @param Promise $promise
     * @param Output $output
     * @param Signer $signer
     * @return Coin
     */
    public static function issue(Promise $promise, Output $output, Signer $signer) {
        return new Coin(new Input(Base::signedBase($promise, $output, $signer), 0));
    }

    /**
     * @param Input[] $inputs
     * @param Output[] $outputs
     * @param Signer $signer
     * @return Coin[]
     */
    public static function transfer($inputs, $outputs, Signer $signer) {
        $transaction = Transaction::signedTransaction($inputs, $outputs, $signer);

        $coins = [];
        foreach ($outputs as $i => $output) {
            $coins[] = new Coin(new Input($transaction, $i));
        }
        return $coins;
    }

    public function confirm($backer, Signer $signer, KeyService $service) {
        $myBases = array_filter($this->getBases(), function (Base $base) use ($backer) {
            return $base->getOutput()->getTarget() == $backer;
        });

        $fractionSum = function (Fraction $sum, Base $base) {
            return $sum->plus($base->getOutput()->getValue());
        };
        $totalBaseSum = array_reduce($this->getBases(), $fractionSum, new Fraction(0));
        $myBaseSum = array_reduce($myBases, $fractionSum, new Fraction(0));

        $target = $this->getOwner();
        $fraction = $this->getValue()->times($myBaseSum)->dividedBy($totalBaseSum);
        $output = new Output($target, $fraction);

        $hash = $service->hash(Signer::squash($this->input->getTransaction()));

        return new Coin(new Input(Confirmation::signedConfirmation($myBases, $output, $hash, $signer), 0));
    }

    private function getBasesOf(Transaction $transaction) {
        if ($transaction instanceof Base) {
            return [$transaction];
        }

        $bases = [];
        foreach ($transaction->getInputs() as $input) {
            $bases = array_merge($bases, $this->getBasesOf($input->getTransaction()));
        }
        return $bases;
    }
}