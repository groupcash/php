<?php
namespace groupcash\php;

use groupcash\php\model\Coin;
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
     * @return string
     */
    public function generateKey() {
        return $this->key->generatePrivateKey();
    }

    /**
     * @param string $key
     * @return string
     */
    public function getAddress($key) {
        return $this->key->publicKey($key);
    }

    /**
     * @param string $promise
     * @param string $backerAddress
     * @param int $serialStart
     * @param int $count
     * @param string $issuerKey
     * @return Coin[]
     */
    public function issueCoins($promise, $backerAddress, $serialStart, $count, $issuerKey) {
        $coins = [];
        for ($i = $serialStart; $i < $serialStart + $count; $i++) {
            $coins[] = Coin::issue(new Promise($backerAddress, $promise, $i), new Signer($this->key, $issuerKey));
        }
        return $coins;
    }

    /**
     * @param Coin $coin
     * @param string $targetAddress
     * @param string $ownerKey
     * @return Coin
     */
    public function transferCoin(Coin $coin, $targetAddress, $ownerKey) {
        return $coin->transfer($targetAddress, new Signer($this->key, $ownerKey));
    }

    /**
     * @param Coin $coin
     * @param array|null $knownIssuerAddresses
     * @return bool
     */
    public function verifyCoin(Coin $coin, array $knownIssuerAddresses = null) {
        $transaction = $coin->getTransaction();
        $signature = $coin->getSignature();

        if (!$this->key->verify($transaction->fingerprint(), $signature->getSigned(), $signature->getSigner())) {
            return false;
        }

        if ($transaction instanceof Promise) {
            if (!is_null($knownIssuerAddresses) && !in_array($coin->getSignature()->getSigner(), $knownIssuerAddresses)) {
                return false;
            }
            return true;

        } else if ($transaction instanceof Transference) {
            if ($coin->getSignature()->getSigner() != $transaction->getCoin()->getTransaction()->getTarget()) {
                return false;
            }
            return $this->verifyCoin($transaction->getCoin(), $knownIssuerAddresses);

        } else {
            return false;
        }
    }

    /**
     * @param Coin $coin
     * @param string $validatedOwnerAddress
     * @param string $backerKey
     * @return Coin
     * @throws \Exception if invalid
     */
    public function validateTransference(Coin $coin, $validatedOwnerAddress, $backerKey) {
        $transference = $coin->getTransaction();

        if ($transference instanceof Promise) {
            if ($transference->getBacker() != $this->key->publicKey($backerKey)) {
                throw new \Exception('Only the backer of a coin can validate it.');
            }
            return $coin;

        } else if ($transference instanceof Transference) {
            if ($transference->getCoin()->getTransaction() instanceof Transference) {
                $validatedCoin = $this->validateTransference($transference->getCoin(), $validatedOwnerAddress, $backerKey);
                $transference = $validatedCoin->getTransaction();
            } else if ($transference->getTarget() != $validatedOwnerAddress) {
                throw new \Exception('Invalid transference.');
            }

            $backer = new Signer($this->key, $backerKey);
            return $transference->getCoin()->transfer($coin->getTransaction()->getTarget(), $backer, 'hash');
        }

        throw new \Exception('Invalid coin.');
    }
}