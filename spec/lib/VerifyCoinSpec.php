<?php
namespace spec\groupcash\php\lib;

use groupcash\php\Groupcash;
use groupcash\php\key\FakeKeyService;
use groupcash\php\model\Authorization;
use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Signature;
use rtens\scrut\Assert;

/**
 * The internal structure of a Coin must comply with several criteria.
 *
 * @property Groupcash lib
 * @property Assert assert <-
 * @property Coin base
 */
class VerifyCoinSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
        $this->base = $this->lib->issueCoin('issuer key', new Promise('coin', 'I promise'), new Output('backer', new Fraction(1)));
    }

    function verifies() {
        $this->lib->verifyCoin($this->base);
        $this->assert->pass();
    }

    function notAuthorized() {
        $this->assertVerificationFails($this->base, [],
            'Not authorized: [issuer]');
    }

    function authorizedForOtherCurrency() {
        $this->assertVerificationFails($this->base, [
            $this->lib->authorizeIssuer('foo key', 'issuer')
        ],
            'Not authorized: [issuer]');
    }

    function authorized() {
        $this->lib->verifyCoin($this->base, [
            $this->lib->authorizeIssuer('coin key', 'issuer')
        ]);
        $this->assert->pass();
    }

    function invalidAuthorization() {
        $this->assertVerificationFails($this->base, [
            new Authorization('issuer', new Signature('coin', 'invalid'))
        ],
            'Invalid authorization: [issuer]');
    }

    function inconsistentCurrencies() {
    }

    function noInputs() {
    }

    function differentOwners() {
    }

    function signedByNonOwner() {
    }

    function invalidSignature() {
    }

    function zeroOutput() {
    }

    function negativeOutput() {
    }

    function overspending() {
    }

    function underspending() {
    }

    function libraryMethod() {
    }

    function collectErrors() {
    }

    private function assertVerificationFails(Coin $coin, $authorizations, $message) {
        try {
            $this->lib->verifyCoin($coin, $authorizations);
            $this->assert->fail('Expected exception');
        } catch (\Exception $e) {
            $this->assert->equals($e->getMessage(), $message);
        }
    }
}