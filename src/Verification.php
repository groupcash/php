<?php
namespace groupcash\php;

use groupcash\php\model\Base;
use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Transaction;

class Verification {

    /** @var string[] */
    private $errors = [];

    /**
     * @param Coin $coin
     */
    public function __construct(Coin $coin) {
        $this->consistentCurrencies($coin);
        $this->traverseTransactions($coin->getInput()->getTransaction(), function (Transaction $transaction) {
            if ($transaction instanceof Base) {
                return;
            }

            if ($this->hasInputs($transaction)) {
                $this->consistentOwners($transaction);
                $this->signedByOwner($transaction);
                $this->inputOutputParity($transaction);
            }
        });
    }

    public function mustBeOk() {
        if ($this->errors) {
            throw new \Exception(implode('; ', $this->errors));
        }
    }

    private function consistentCurrencies(Coin $coin) {
        $currencies = array_unique(array_map(function (Base $base) {
            return $base->getPromise()->getCurrency();
        }, $coin->getBases()));

        if (count($currencies) > 1) {
            $this->errors[] = 'Inconsistent currencies: [' . implode('], [', $currencies) . ']';
        }
    }

    private function traverseTransactions(Transaction $transaction, callable $call) {
        $call($transaction);
        foreach ($transaction->getInputs() as $input) {
            $this->traverseTransactions($input->getTransaction(), $call);
        }
    }

    private function hasInputs(Transaction $transaction) {
        if (!$transaction->getInputs()) {
            $this->errors[] = 'No inputs';
            return false;
        }

        return true;
    }

    private function consistentOwners(Transaction $transaction) {
        $owners = [];
        foreach ($transaction->getInputs() as $input) {
            $owners[] = $input->getOutput()->getTarget();
        }

        $uniqueOwners = array_unique($owners);
        if (count($uniqueOwners) != 1) {
            $this->errors[] = 'Inconsistent owners: [' . implode('], [', $uniqueOwners) . ']';
        }
    }

    private function signedByOwner(Transaction $transaction) {
        $inputs = $transaction->getInputs();
        $owner = $inputs[0]->getOutput()->getTarget();

        $signer = $transaction->getSignature()->getSigner();
        if ($signer != $owner) {
            $this->errors[] = "Signed by non-owner: [$signer]";
        }
    }

    private function inputOutputParity(Transaction $transaction) {
        $outputSum = new Fraction(0);
        foreach ($transaction->getOutputs() as $output) {
            if ($output->getValue()->isLessThan(new Fraction(0))) {
                $this->errors[] = 'Negative output value';
            } else if ($output->getValue() == new Fraction(0)) {
                $this->errors[] = 'Zero output value';
            }

            $outputSum = $outputSum->plus($output->getValue());
        }

        $inputSum = array_reduce($transaction->getInputs(), function (Fraction $sum, Input $input) {
            return $sum->plus($input->getOutput()->getValue());
        }, new Fraction(0));

        if ($inputSum != $outputSum) {
            $this->errors[] = 'Output sum not equal input sum';
        }
    }
}