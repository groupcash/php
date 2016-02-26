<?php
namespace spec\groupcash\php\lib;

use groupcash\php\Groupcash;
use groupcash\php\io\CoinSerializer;
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
 * @property Coin one
 * @property Coin two
 */
class VerifyCoinSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
        $this->base = $this->lib->issueCoin('issuer key', new Promise('coin', 'I promise'), new Output('backer', new Fraction(1)));

        $this->one = $this->lib->transferCoins('backer key', [
            $this->lib->issueCoin('i key', new Promise('coin', 'p1'), new Output('backer', new Fraction(1))),
            $this->lib->issueCoin('i key', new Promise('coin', 'p2'), new Output('backer', new Fraction(2)))
        ], [
            new Output('one', new Fraction(3))
        ])[0];

        $this->two = $this->lib->transferCoins('one key', [
            $this->lib->transferCoins('a key', [
                $this->lib->issueCoin('i key', new Promise('coin', 'p3'), new Output('a', new Fraction(5))),
                $this->lib->issueCoin('i key', new Promise('coin', 'p4'), new Output('a', new Fraction(7)))
            ], [
                new Output('one', new Fraction(12))
            ])[0],
            $this->lib->transferCoins('b key', [
                $this->lib->issueCoin('i key', new Promise('coin', 'p5'), new Output('b', new Fraction(11)))
            ], [
                new Output('one', new Fraction(11))
            ])[0],
            $this->one
        ], [
            new Output('two', new Fraction(26))
        ])[0];
    }

    function verifies() {
        $this->lib->verifyCoin($this->base);
        $this->assert->pass();
    }

    function notAuthorized() {
        $this->assertNotAuthorized('Not authorized: [issuer]', $this->base, []);
    }

    function authorizedForOtherCurrency() {
        $this->assertNotAuthorized('Not authorized: [issuer]', $this->base, [
            $this->lib->authorizeIssuer('foo key', 'issuer')
        ]);
    }

    function authorized() {
        $this->lib->verifyCoin($this->base, [
            $this->lib->authorizeIssuer('coin key', 'issuer')
        ]);
        $this->assert->pass();
    }

    function invalidAuthorization() {
        $this->assertNotAuthorized('Invalid authorization: [issuer]', $this->base, [
            new Authorization('issuer', new Signature('coin', 'invalid'))
        ]);
    }

    function inconsistentCurrencies() {
        $this->assertFail('Inconsistent currencies: [coin], [not coin]', $this->one, function ($tx) {
            $tx->ins[1]->tx->promise->currency = 'not coin';
            $this->replaceSign('coin//p2', 'not coin//p2', [$tx->ins[1]->tx->sig, $tx->sig]);
        });
    }

    function noInputs() {
        $this->assertFail('No inputs', $this->two, function ($tx) {
            $tx->ins[1]->tx->ins = [];
            $this->replaceSign('coin//p5//b//11|1//0', '', [$tx->ins[1]->tx->sig, $tx->sig]);
        });
    }

    function differentOwners() {
        $this->assertFail('Inconsistent owners: [one], [not one]', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[0]->to = 'not one';
            $this->replaceSign('one//11', 'not one//11', [$tx->ins[1]->tx->sig, $tx->sig]);
        });
    }

    function signedByNonOwner() {
        $this->assertFail('Signed by non-owner: [not b]', $this->two, function ($tx) {
            $tx->ins[1]->tx->sig->signer = 'not b';
            $this->replaceSign('b key', 'not b key', [$tx->ins[1]->tx->sig]);
        });
    }

    function invalidSignature() {
        $this->assertFail('Invalid signature by [b]', $this->two, function ($tx) {
            $tx->ins[1]->tx->sig->sign = 'invalid';
        });
    }

    function zeroOutput() {
    }

    function negativeOutput() {
    }

    function overspending() {
    }

    function underspending() {
    }

    function doubleSpending() {
    }

    function collectErrors() {
    }

    private function assertNotAuthorized($message, Coin $coin, $authorizations) {
        try {
            $this->lib->verifyCoin($coin, $authorizations);
        } catch (\Exception $e) {
            $this->assert->equals($e->getMessage(), $message);
            return;
        }

        $this->assert->fail('No exception thrown');
    }

    private function assertFail($message, Coin $coin, callable $modify) {
        $serializer = new CoinSerializer();
        $serialized = json_decode(substr($serializer->serialize($coin), strlen(CoinSerializer::SERIALIZER_ID)));
        $modify($serialized->in->tx);
        $inflated = $serializer->deserialize(CoinSerializer::SERIALIZER_ID . json_encode($serialized));
        $this->assertNotAuthorized($message, $inflated, null);
    }

    private function replaceSign($find, $replace, $sigs) {
        foreach ($sigs as $sig) {
            $sig->sign = str_replace(str_replace("//", "\0", $find), str_replace('//', "\0", $replace), $sig->sign);
        }
    }
}