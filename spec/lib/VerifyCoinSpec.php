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

        $a = $this->lib->transferCoins('a key', [
            $this->lib->issueCoin('i key', new Promise('coin', 'p3'), new Output('a', new Fraction(5))),
            $this->lib->issueCoin('i key', new Promise('coin', 'p4'), new Output('a', new Fraction(7)))
        ], [
            new Output('one', new Fraction(4)),
            new Output('one', new Fraction(6)),
            new Output('b', new Fraction(2))
        ]);

        $this->two = $this->lib->transferCoins('one key', [
            $a[0],
            $this->lib->transferCoins('b key', [
                $this->lib->issueCoin('i key', new Promise('coin', 'p5'), new Output('b', new Fraction(13))),
                $a[2]
            ], [
                new Output('one', new Fraction(10)),
                new Output('x', new Fraction(5))
            ])[0],
            $this->one,
            $a[1]
        ], [
            new Output('two', new Fraction(23))
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
            $tx->ins[1]->tx->promise[0] = 'not coin';
            $this->replaceSign(['coin//p2' => 'not coin//p2'], [$tx->ins[1]->tx->sig, $tx->sig]);
        });
    }

    function noInputs() {
        $this->assertFail('No inputs', $this->two, function ($tx) {
            $tx->ins[2]->tx->ins = [];
            $this->replaceSign(['coin//p1//backer//1|1//0//coin//p2//backer//2|1//0' => ''], [$tx->ins[2]->tx->sig, $tx->sig]);
        });
    }

    function differentOwners() {
        $this->assertFail('Inconsistent owners: [one], [not one]', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[0]->to = 'not one';
            $this->replaceSign(['one//10' => 'not one//10'], [$tx->ins[1]->tx->sig, $tx->sig]);
        });
    }

    function signedByNonOwner() {
        $this->assertFail('Signed by non-owner: [not b]', $this->two, function ($tx) {
            $tx->ins[1]->tx->sig->by = 'not b';
            $this->replaceSign(['b key' => 'not b key'], [$tx->ins[1]->tx->sig]);
        });
    }

    function invalidSignature() {
        $this->assertFail('Invalid signature by [b]', $this->two, function ($tx) {
            $tx->ins[1]->tx->sig->sign = 'invalid';
        });
    }

    function zeroOutput() {
        $this->assertFail('Zero output value', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val = 0;
            $tx->ins[1]->tx->ins[0]->tx->out->val -= 5;
            $this->replaceSign(['x//5|1' => 'x//0|1', '//13|1' => '//8|1'], [
                $tx->ins[1]->tx->ins[0]->tx->sig,
                $tx->ins[1]->tx->sig,
                $tx->sig
            ]);
        });
    }

    function negativeOutput() {
        $this->assertFail('Negative output value', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val = -1;
            $tx->ins[1]->tx->ins[0]->tx->out->val -= 6;
            $this->replaceSign(['x//5|1' => 'x//-1|1', '//13|1' => '//7|1'], [
                $tx->ins[1]->tx->ins[0]->tx->sig,
                $tx->ins[1]->tx->sig,
                $tx->sig
            ]);
        });
    }

    function overspending() {
        $this->assertFail('Output sum greater than input sum', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val += 1;
            $this->replaceSign(['x//5|1' => 'x//6|1'], [
                $tx->ins[1]->tx->ins[0]->tx->sig,
                $tx->ins[1]->tx->sig,
                $tx->sig
            ]);
        });
    }

    function underspending() {
        $this->assertFail('Output sum less than input sum', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val -= 1;
            $this->replaceSign(['x//5|1' => 'x//4|1'], [
                $tx->ins[1]->tx->ins[0]->tx->sig,
                $tx->ins[1]->tx->sig,
                $tx->sig
            ]);
        });
    }

    function notExistingOutput() {
        $this->assertFail('Invalid output index', $this->two, function ($tx) {
            $tx->ins[3]->iout = 42;
            $this->replaceSign(['1//two' => '42//two'], [$tx->sig]);
        });
    }

    function doubleSpendingInSameTransaction() {
        $this->assertFail('Output already used', $this->two, function ($tx) {
            $tx->ins[3]->iout = 0;
            $tx->outs[0]->val -= 2;
            $this->replaceSign(['1//two//23|1' => '0//two//21|1'], [$tx->sig]);
        });
    }

    function doubleSpending() {
        $this->assertFail('Output already used', $this->two, function ($tx) {
            $tx->ins[1]->tx->ins[1]->iout = 0;
            $tx->ins[1]->tx->ins[1]->tx->outs[0]->to = 'b';
            $tx->ins[1]->tx->outs[1]->val += 2;
            $this->replaceSign([
                '0//one//4|1//one//6|1//b//2|1//2//one//10|1//x//5' => '0//b//4|1//one//6|1//b//2|1//0//one//10|1//x//7'
            ], [$tx->sig, $tx->ins[1]->tx->sig]);
            $this->replaceSign(['0//one//4' => '0//b//4'], [$tx->ins[1]->tx->ins[1]->tx->sig]);
        });
    }

    function multipleErrors() {
        $this->assertFail(
            'Invalid signature by [one]; ' .
            'Invalid signature by [a]; ' .
            'Zero output value; ' .
            'Output sum less than input sum; ' .
            'Output already used',
            $this->two,
            function ($tx) {
                $tx->ins[0]->tx->outs[1]->val = 0;
            });
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
        $serialized = json_decode(substr($serializer->serialize($coin), strlen('__COIN_JSON_A__')));
        $modify($serialized->in->tx);

        /** @var Coin $inflated */
        $inflated = $serializer->inflate(CoinSerializer::TOKEN . json_encode($serialized));
        $this->assertNotAuthorized($message, $inflated, null);
    }

    private function replaceSign($replace, $sigs) {
        foreach ($sigs as $sig) {
            $sig->sign = str_replace(
                str_replace("//", "\0", array_keys($replace)),
                str_replace('//', "\0", array_values($replace)),
                $sig->sign);
        }
    }
}