<?php
namespace spec\groupcash\php\lib;

use groupcash\php\Groupcash;
use groupcash\php\io\CoinSerializer;
use groupcash\php\io\transcoders\JsonTranscoder;
use groupcash\php\key\FakeKeyService;
use groupcash\php\model\Authorization;
use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
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
            new Authorization('issuer', 'coin', 'invalid')
        ]);
    }

    function inconsistentCurrencies() {
        $this->assertFail('Inconsistent currencies: [coin], [not coin]', $this->one, function ($tx) {
            $tx->ins[1]->tx->promise[0] = 'not coin';
            $this->replaceSigs(['coin//p2' => 'not coin//p2'], [$tx->ins[1]->tx, $tx]);
        });
    }

    function noInputs() {
        $this->assertFail('No inputs', $this->two, function ($tx) {
            $tx->ins[2]->tx->ins = [];
            $this->replaceSigs(['coin//p1//backer//1|1//0//coin//p2//backer//2|1//0' => ''], [$tx->ins[2]->tx, $tx]);
        });
    }

    function differentOwners() {
        $this->assertFail('Inconsistent owners: [one], [not one]', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[0]->to = 'not one';
            $this->replaceSigs(['one//10' => 'not one//10'], [$tx->ins[1]->tx, $tx]);
        });
    }

    function invalidSignature() {
        $this->assertFail('Not signed by owner [b]', $this->two, function ($tx) {
            $tx->ins[1]->tx->sig = 'invalid';
        });
    }

    function zeroOutput() {
        $this->assertFail('Zero output value', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val = 0;
            $tx->ins[1]->tx->ins[0]->tx->out->val -= 5;
            $this->replaceSigs(['x//5|1' => 'x//0|1', '//13|1' => '//8|1'], [
                $tx->ins[1]->tx->ins[0]->tx,
                $tx->ins[1]->tx,
                $tx
            ]);
        });
    }

    function negativeOutput() {
        $this->assertFail('Negative output value', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val = -1;
            $tx->ins[1]->tx->ins[0]->tx->out->val -= 6;
            $this->replaceSigs(['x//5|1' => 'x//-1|1', '//13|1' => '//7|1'], [
                $tx->ins[1]->tx->ins[0]->tx,
                $tx->ins[1]->tx,
                $tx
            ]);
        });
    }

    function overspending() {
        $this->assertFail('Output sum greater than input sum', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val += 1;
            $this->replaceSigs(['x//5|1' => 'x//6|1'], [
                $tx->ins[1]->tx->ins[0]->tx,
                $tx->ins[1]->tx,
                $tx
            ]);
        });
    }

    function underspending() {
        $this->assertFail('Output sum less than input sum', $this->two, function ($tx) {
            $tx->ins[1]->tx->outs[1]->val -= 1;
            $this->replaceSigs(['x//5|1' => 'x//4|1'], [
                $tx->ins[1]->tx->ins[0]->tx,
                $tx->ins[1]->tx,
                $tx
            ]);
        });
    }

    function notExistingOutput() {
        $this->assertFail('Invalid output index', $this->two, function ($tx) {
            $tx->ins[3]->iout = 42;
            $this->replaceSigs(['1//two' => '42//two'], [$tx]);
        });
    }

    function doubleSpendingInSameTransaction() {
        $this->assertFail('Output already used', $this->two, function ($tx) {
            $tx->ins[3]->iout = 0;
            $tx->outs[0]->val -= 2;
            $this->replaceSigs(['1//two//23|1' => '0//two//21|1'], [$tx]);
        });
    }

    function doubleSpending() {
        $this->assertFail('Output already used', $this->two, function ($tx) {
            $tx->ins[1]->tx->ins[1]->iout = 0;
            $tx->ins[1]->tx->ins[1]->tx->outs[0]->to = 'b';
            $tx->ins[1]->tx->outs[1]->val += 2;
            $this->replaceSigs([
                '0//one//4|1//one//6|1//b//2|1//2//one//10|1//x//5' => '0//b//4|1//one//6|1//b//2|1//0//one//10|1//x//7'
            ], [$tx, $tx->ins[1]->tx]);
            $this->replaceSigs(['0//one//4' => '0//b//4'], [$tx->ins[1]->tx->ins[1]->tx]);
        });
    }

    function multipleErrors() {
        $this->assertFail(
            'Not signed by owner [one]; ' .
            'Not signed by owner [a]; ' .
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
        $json = new JsonTranscoder();
        $serializer = new CoinSerializer([$json]);

        $serialized = json_decode(json_encode($json->decode($serializer->serialize($coin))[1]));
        $modify($serialized->in->tx);
        $encoded = $json->encode([CoinSerializer::TOKEN, json_decode(json_encode($serialized), true)]);

        /** @var Coin $inflated */
        $inflated = $serializer->inflate($encoded);
        $this->assertNotAuthorized($message, $inflated, null);
    }

    private function replaceSigs($replace, $txs) {
        foreach ($txs as $tx) {
            $tx->sig = str_replace(
                str_replace("//", "\0", array_keys($replace)),
                str_replace('//', "\0", array_values($replace)),
                $tx->sig);
        }
    }
}