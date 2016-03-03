<?php
namespace groupcash\php;

use groupcash\php\model\Authorization;
use groupcash\php\model\Coin;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\KeyService;
use groupcash\php\model\signing\Signer;

class Groupcash {

    /** @var KeyService */
    private $key;

    /**
     * @param KeyService $key
     */
    public function __construct(KeyService $key) {
        $this->key = $key;
    }

    /**
     * Generates a new private key.
     *
     * @return \groupcash\php\model\signing\Binary
     */
    public function generateKey() {
        return $this->key->generatePrivateKey();
    }

    /**
     * Displays the public key corresponding to the given private key.
     *
     * @param Binary $key
     * @return \groupcash\php\model\signing\Binary
     */
    public function getAddress(Binary $key) {
        return $this->key->publicKey($key);
    }

    /**
     * Creates a new coin based on a delivery promise.
     *
     * @param \groupcash\php\model\signing\Binary $issuerKey
     * @param Promise $promise
     * @param Output $output
     * @return Coin
     */
    public function issueCoin(Binary $issuerKey, Promise $promise, Output $output) {
        return Coin::issue($promise, $output, new Signer($this->key, $issuerKey));
    }

    /**
     * Transfers the values of one or more coins to one or more targets.
     *
     * @param \groupcash\php\model\signing\Binary $ownerKey
     * @param Coin[] $coins
     * @param Output[] $outputs
     * @return \groupcash\php\model\Coin[]
     * @throws \Exception
     */
    public function transferCoins(Binary $ownerKey, array $coins, array $outputs) {
        $inputs = array_map(function (Coin $coin) {
            return $coin->getInput();
        }, $coins);

        $transferred = Coin::transfer($inputs, $outputs, new Signer($this->key, $ownerKey));
        (new Verification($this->key))->verifyAll($transferred)->mustBeOk();
        return $transferred;
    }

    /**
     * Creates a new coin with a value proportional to the bases of the backer.
     *
     * @param \groupcash\php\model\signing\Binary $backerKey
     * @param Coin $coin
     * @return Coin
     * @throws \Exception
     */
    public function confirmCoin(Binary $backerKey, Coin $coin) {
        $backer = $this->key->publicKey($backerKey);
        $confirmed = $coin->confirm($backer, new Signer($this->key, $backerKey));

        (new Verification($this->key))->verify($confirmed)->mustBeOk();
        return $confirmed;
    }

    /**
     * Signs an Authorization for the given issuer with the currency's key
     *
     * @param \groupcash\php\model\signing\Binary $currencyKey
     * @param \groupcash\php\model\signing\Binary $issuerAddress
     * @return Authorization
     */
    public function authorizeIssuer(Binary $currencyKey, Binary $issuerAddress) {
        return Authorization::signed($issuerAddress, new Signer($this->key, $currencyKey));
    }

    /**
     * Verifies that the Coin is internally consistent.
     *
     * @param Coin $coin
     * @param Authorization[]|null $authorizations
     * @throws \Exception
     * @throw Exception if Coin does not verify
     */
    public function verifyCoin(Coin $coin, $authorizations = null) {
        $verification = new Verification($this->key);
        $verification->verify($coin);
        if (!is_null($authorizations)) {
            $verification->verifyAuthorizations($coin, $authorizations);
        }
        $verification->mustBeOk();
    }
}