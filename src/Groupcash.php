<?php
namespace groupcash\php;

use groupcash\php\model\Authorization;
use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Promise;
use groupcash\php\model\Signer;
use groupcash\php\model\Transference;

class Groupcash {

    /** @var KeyService */
    private $key;

    public function __construct(KeyService $key) {
        $this->key = $key;
    }

    /**
     * Generates a new private key.
     *
     * @return string
     */
    public function generateKey() {
        return $this->key->generatePrivateKey();
    }

    /**
     * Displays the public key corresponding to the given private key.
     *
     * @param string $key
     * @return string
     */
    public function getAddress($key) {
        return $this->key->publicKey($key);
    }

    /**
     * Creates a new coin representing a delivery promise.
     *
     * @param string $currency
     * @param string $issuerKey
     * @param string $promise
     * @param string $backerAddress
     * @param int $serialStart
     * @param int $count
     * @return model\Coin[]
     */
    public function issueCoins($currency, $issuerKey, $promise, $backerAddress, $serialStart, $count) {
        $issuer = new Signer($this->key, $issuerKey);

        $coins = [];
        for ($i = $serialStart; $i < $serialStart + $count; $i++) {
            $coins[] = Coin::issue(new Promise($currency, $backerAddress, $promise, $i), $issuer);
        }
        return $coins;
    }

    /**
     * Transfers a coin to a new target owner.
     *
     * @param string $ownerKey
     * @param Coin $coin
     * @param string $targetAddress
     * @param Fraction|null $fraction
     * @return Coin
     */
    public function transferCoin($ownerKey, Coin $coin, $targetAddress, Fraction $fraction = null) {
        $owner = new Signer($this->key, $ownerKey);
        return $coin->transfer($targetAddress, $owner, $fraction);
    }

    /**
     * Validates that a coin was not double-spent.
     *
     * @param string $backerKey
     * @param Coin $coin
     * @return Coin
     * @throws \Exception if invalid
     */
    public function validateCoin($backerKey, Coin $coin) {
        $transference = $coin->getTransaction();

        if ($transference instanceof Promise) {
            return $coin;
        } else {
            $backer = new Signer($this->key, $backerKey);
            return $this->validateTransference($backer, $coin);
        }
    }

    private function validateTransference(Signer $signer, Coin $coin) {
        $transference = $coin->getTransaction();

        if ($transference instanceof Promise) {
            if ($transference->getBacker() != $signer->getAddress()) {
                throw new \Exception('Only the backer of a coin can validate it.');
            }
            return $coin->transfer(
                null,
                $signer,
                new Fraction(1)
            );

        } else if ($transference instanceof Transference) {
            /** @var Transference $issued */
            $issued = $this->validateTransference($signer, $transference->getCoin())->getTransaction();

            return $issued->getCoin()->transfer(
                $transference->getTarget(),
                $signer,
                $transference->getFraction()->times($issued->getFraction()),
                $this->key->hash($issued->fingerprint()));
        }

        throw new \Exception('Invalid coin.');
    }

    /**
     * Verifies that all transactions of a coin are sound.
     *
     * @param Coin $coin
     * @param null|Authorization[] $authorizedIssuers
     * @return bool
     */
    public function verifyCoin(Coin $coin, array $authorizedIssuers = null) {
        $transaction = $coin->getTransaction();
        $signature = $coin->getSignature();

        if (!$this->key->verify($transaction->fingerprint(), $signature->getSigned(), $signature->getSigner())) {
            return false;
        }

        if ($transaction instanceof Promise) {
            if (!$authorizedIssuers) {
                return true;
            }
            foreach ($authorizedIssuers as $issuer) {
                if ($issuer->isAuthorizedToIssue($transaction, $coin->getSignature(), $this->key)) {
                    return true;
                }
            }
            return false;

        } else if ($transaction instanceof Transference) {
            if ($coin->getSignature()->getSigner() != $transaction->getCoin()->getTransaction()->getTarget()) {
                return false;
            }
            return $this->verifyCoin($transaction->getCoin(), $authorizedIssuers);

        } else {
            return false;
        }
    }

    /**
     * Resolves all transactions of a coin into changes of balances.
     *
     * @param Coin $coin
     * @return Fraction[] indexed by addresses
     */
    public function resolveTransactions(Coin $coin) {
        /** @var Transference[] $transferences */
        $transferences = [];

        $transaction = $coin->getTransaction();
        while ($transaction instanceof Transference) {
            $transferences[] = $transaction;
            $transaction = $transaction->getCoin()->getTransaction();
        }

        $transferences = array_reverse($transferences);

        $fractions = [];
        $lastOwner = null;
        $fraction = new Fraction(1);

        foreach ($transferences as $transference) {
            if ($lastOwner) {
                $fraction = $fraction->times($transference->getFraction());

                $fractions[$lastOwner][] = $fraction->times(new Fraction(-1, 1));
                $fractions[$transference->getTarget()][] = $fraction;
            }

            $lastOwner = $transference->getTarget();
        }

        /** @var Fraction[] $balances */
        $balances = [];
        foreach ($fractions as $member => $theirFractions) {
            $balances[$member] = new Fraction(0, 1);
            foreach ($theirFractions as $fraction) {
                $balances[$member] = $balances[$member]->plus($fraction);
            }
        }
        return $balances;
    }
}