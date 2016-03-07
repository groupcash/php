<?php
namespace groupcash\php;

use groupcash\php\model\Authorization;
use groupcash\php\model\Coin;
use groupcash\php\model\RuleBook;
use groupcash\php\model\Output;
use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Algorithm;
use groupcash\php\model\signing\Signer;

class Groupcash {

    /** @var Algorithm */
    private $key;

    /**
     * @param Algorithm $key
     */
    public function __construct(Algorithm $key) {
        $this->key = $key;
    }

    /**
     * Generates a new private key.
     *
     * @return Binary
     */
    public function generateKey() {
        return $this->key->generateKey();
    }

    /**
     * Displays the public key corresponding to the given private key.
     *
     * @param Binary $key
     * @return Binary
     */
    public function getAddress(Binary $key) {
        return $this->key->getAddress($key);
    }

    /**
     * @param Binary $currencyKey
     * @param string $rules
     * @param null|RuleBook $previous
     * @return RuleBook
     */
    public function signRules(Binary $currencyKey, $rules, RuleBook $previous = null) {
        $address = $this->key->getAddress($currencyKey);
        $book = RuleBook::signed(new Signer($this->key, $currencyKey),
            $address, $rules, $previous);

        $allRules = [$book];
        if ($previous) {
            $allRules[] = $previous;
        }

        (new Verification($this->key))->verifyCurrencyRules($allRules)->mustBeOk();
        return $book;
    }

    /**
     * Signs an Authorization for the given issuer with the currency's key
     *
     * @param Binary $currencyKey
     * @param Binary $issuerAddress
     * @return Authorization
     */
    public function authorizeIssuer(Binary $currencyKey, Binary $issuerAddress) {
        return Authorization::signed($issuerAddress, new Signer($this->key, $currencyKey));
    }

    /**
     * Creates a new coin based on a delivery promise.
     *
     * @param Binary $issuerKey
     * @param Binary $currency
     * @param string $description
     * @param Output $output
     * @return Coin
     */
    public function issueCoin(Binary $issuerKey, Binary $currency, $description, Output $output) {
        return Coin::issue($description, $currency, $output, new Signer($this->key, $issuerKey));
    }

    /**
     * Transfers the values of one or more coins to one or more targets.
     *
     * @param Binary $ownerKey
     * @param Coin[] $coins
     * @param Output[] $outputs
     * @return Coin[]
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
     * @param Binary $backerKey
     * @param Coin $coin
     * @return Coin
     * @throws \Exception
     */
    public function confirmCoin(Binary $backerKey, Coin $coin) {
        $backer = $this->key->getAddress($backerKey);
        $confirmed = $coin->confirm($backer, new Signer($this->key, $backerKey));

        (new Verification($this->key))->verify($confirmed)->mustBeOk();
        return $confirmed;
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

    /**
     * @param RuleBook[] $rules
     */
    public function verifyCurrencyRules(array $rules) {
        (new Verification($this->key))->verifyCurrencyRules($rules)->mustBeOk();
    }
}