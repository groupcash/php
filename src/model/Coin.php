<?php
namespace groupcash\php\model;

use groupcash\php\Finger;
use groupcash\php\Signer;

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
     * @param Promise $promise
     * @param Output $output
     * @param Signer $signer
     * @return Coin
     */
    public static function issue(Promise $promise, Output $output, Signer $signer) {
        return new Coin(new Base($promise, $output, $signer->sign([[$promise], [$output]])), 0);
    }

    /**
     * @param Coin[] $coins
     * @param Output[] $outputs
     * @param Signer $signer
     * @return Coin[]
     */
    public static function transfer($coins, $outputs, Signer $signer) {
        $transaction = new Transaction($coins, $outputs, $signer->sign([$coins, $outputs]));

        $coins = [];
        foreach ($outputs as $i => $output) {
            $coins[] = new Coin($transaction, $i);
        }
        return $coins;
    }

    public function confirm($backer, Signer $signer, Finger $finger) {
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

        $fingerprint = $finger->makePrint($this->getTransaction());
        $signature = $signer->sign([$myBases, $output, $fingerprint]);

        return new Coin(new Confirmation($myBases, $output, $fingerprint, $signature), 0);
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

    /**
     * @return Base[]
     */
    public function getBases() {
        return $this->getBasesOf($this->getTransaction());
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