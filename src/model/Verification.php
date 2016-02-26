<?php
namespace groupcash\php\model;

use groupcash\php\KeyService;

class Verification {

    /** @var KeyService */
    private $key;

    /** @var string[] */
    private $errors = [];

    /**
     * @param KeyService $key
     */
    public function __construct(KeyService $key) {
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
                $this->verifySignature($transaction);

                if ($transaction instanceof Base) {
                    return $acc;
                } else if ($this->hasInputs($transaction) && $this->outputsExist($transaction)) {
                    $this->consistentOwners($transaction);
                    $this->signedByOwner($transaction);
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
        $currency = $bases[0]->getPromise()->getCurrency();

        foreach ($authorizations as $authorization) {
            $hash = $this->key->hash(Signer::squash($authorization));
            if (!$this->key->verify($hash, $authorization->getSignature())) {
                $this->errors[] = "Invalid authorization: [{$authorization->getPrint()}]";
            }
        }

        foreach ($bases as $base) {
            $issuer = $base->getSignature()->getSigner();
            if (!$this->isAuthorized($issuer, $currency, $authorizations)) {
                $this->errors[] = "Not authorized: [$issuer]";
            }
        }

        return $this;
    }

    /**
     * @param string $issuer
     * @param string $currency
     * @param Authorization[] $authorizations
     * @return bool
     */
    private function isAuthorized($issuer, $currency, $authorizations) {
        foreach ($authorizations as $authorization) {
            if ($authorization->authorizes($issuer, $currency)) {
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
            return $base->getPromise()->getCurrency();
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

        if ($inputSum < $outputSum) {
            $this->errors[] = 'Output sum greater than input sum';
        } else if ($inputSum > $outputSum) {
            $this->errors[] = 'Output sum less than input sum';
        }
    }

    private function verifySignature(Transaction $transaction) {
        $hash = $this->key->hash(Signer::squash($transaction));
        if (!$this->key->verify($hash, $transaction->getSignature())) {
            $this->errors[] = "Invalid signature by [{$transaction->getSignature()->getSigner()}]";
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