<?php
namespace groupcash\php;

use groupcash\php\model\Authorization;
use groupcash\php\model\Base;
use groupcash\php\model\Coin;
use groupcash\php\model\Input;
use groupcash\php\model\signing\Algorithm;
use groupcash\php\model\signing\Signer;
use groupcash\php\model\Transaction;
use groupcash\php\model\value\Fraction;

class Verification {

    /** @var Algorithm */
    private $key;

    /** @var string[] */
    private $errors = [];

    /**
     * @param Algorithm $key
     */
    public function __construct(Algorithm $key) {
        $this->key = $key;
    }

    /**
     * @param Coin $coin
     * @return Verification
     */
    public function verify(Coin $coin) {
        $this->consistentCurrencies($coin);
        $this->traverseTransactions($coin->getInput()->getTransaction(),
            function (Transaction $transaction, $acc) {

                if ($transaction instanceof Base) {
                    $this->verifyBaseSignature($transaction);
                } else if ($this->hasInputs($transaction) && $this->outputsExist($transaction)) {
                    $this->verifySignature($transaction);
                    $this->consistentOwners($transaction);
                    $this->inputOutputParity($transaction);
                    $acc = $this->uniqueInput($transaction, $acc);
                }

                return $acc;
            });

        return $this;
    }

    /**
     * @param Coin[] $coins
     * @return Verification
     */
    public function verifyAll($coins) {
        foreach ($coins as $coin) {
            $this->verify($coin);
        }

        return $this;
    }

    /**
     * @param Coin $coin
     * @param Authorization[] $authorizations
     * @return Verification
     */
    public function verifyAuthorizations(Coin $coin, $authorizations) {
        $bases = $coin->getBases();
        $currency = $bases[0]->getCurrency();

        /** @var Authorization[] $authorizedByCurrency */
        $authorizedByCurrency = array_filter($authorizations, function (Authorization $authorization) use ($currency) {
            return $authorization->getCurrencyAddress() == $currency;
        });

        foreach ($authorizedByCurrency as $authorization) {
            if (!$this->key->verify(Signer::squash($authorization), $currency, $authorization->getSignature())) {
                $this->errors[] = "Invalid authorization: [{$authorization->getIssuerAddress()}]";
            }
        }

        foreach ($bases as $base) {
            $issuer = $base->getIssuerAddress();
            if (!$this->isAuthorized($issuer, $authorizedByCurrency)) {
                $this->errors[] = "Not authorized: [$issuer]";
            }
        }

        return $this;
    }

    /**
     * @param string $issuer
     * @param Authorization[] $authorizations
     * @return bool
     */
    private function isAuthorized($issuer, $authorizations) {
        foreach ($authorizations as $authorization) {
            if ($authorization->getIssuerAddress() == $issuer) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getErrors() {
        return $this->errors;
    }

    public function isOk() {
        return !$this->errors;
    }

    public function mustBeOk() {
        if (!$this->isOk()) {
            throw new \Exception(implode('; ', array_unique($this->errors)));
        }
    }

    private function consistentCurrencies(Coin $coin) {
        $currencies = array_unique(array_map(function (Base $base) {
            return $base->getCurrency();
        }, $coin->getBases()));

        if (count($currencies) > 1) {
            $this->errors[] = 'Inconsistent currencies: [' . implode('], [', $currencies) . ']';
        }
    }

    private function traverseTransactions(Transaction $transaction, callable $call, $acc = []) {
        $acc = $call($transaction, $acc);
        foreach ($transaction->getInputs() as $input) {
            $acc = $this->traverseTransactions($input->getTransaction(), $call, $acc);
        }
        return $acc;
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

        if ($inputSum < $outputSum) {
            $this->errors[] = 'Output sum greater than input sum';
        } else if ($inputSum > $outputSum) {
            $this->errors[] = 'Output sum less than input sum';
        }
    }

    private function verifySignature(Transaction $transaction) {
        $owner = $transaction->getInputs()[0]->getOutput()->getTarget();

        if (!$this->key->verify(Signer::squash($transaction), $owner, $transaction->getSignature())) {
            $this->errors[] = "Not signed by owner [$owner]";
        }
    }

    private function verifyBaseSignature(Base $transaction) {
        $issuer = $transaction->getIssuerAddress();

        if (!$this->key->verify(Signer::squash($transaction), $issuer, $transaction->getSignature())) {
            $this->errors[] = "Invalid signature by [{$issuer}]";
        }
    }

    private function outputsExist(Transaction $transaction) {
        $exists = true;
        foreach ($transaction->getInputs() as $input) {
            if (!array_key_exists($input->getOutputIndex(), $input->getTransaction()->getOutputs())) {
                $this->errors[] = 'Invalid output index';
                $exists = false;
            }
        }
        return $exists;
    }

    private function uniqueInput(Transaction $transaction, $acc) {
        $inputs = isset($acc['inputs']) ? $acc['inputs'] : [];
        $transactions = isset($acc['transactions']) ? $acc['transactions'] : [];

        $squashedTransaction = Signer::squash($transaction);
        $acc['transactions'][] = $squashedTransaction;
        if (in_array($squashedTransaction, $transactions)) {
            return $acc;
        }

        foreach ($transaction->getInputs() as $input) {
            $squashed = Signer::squash($input);
            if (in_array($squashed, $inputs)) {
                $this->errors[] = "Output already used";
            }
            $inputs[] = $squashed;
        }

        $acc['inputs'] = $inputs;
        return $acc;
    }
}