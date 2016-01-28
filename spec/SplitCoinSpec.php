<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Fraction;
use groupcash\php\model\SplitCoin;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * In order to support micro-payments, fractions of coins can be transferred. Rounding erros can be avoided by fixing
 * the denominator and storing only the integer nominator. For validation, the fraction that each participant is
 * entitled to needs to be kept track of.
 *
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 * @property Groupcash lib
 */
class SplitCoinSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function intoZero() {
        $coins = $this->lib->issueCoins('promise', 'public backer', 1, 1, 'issuer');
        $coin = $this->lib->splitCoin($coins[0], []);

        $this->assert->equals($coin, $coins[0]);
    }

    function intoOne() {
        $coins = $this->lib->issueCoins('promise', 'public backer', 1, 1, 'issuer');
        $coin = $this->lib->splitCoin($coins[0], [42]);

        $this->assert->equals($coin, $coins[0]);
    }

    function intoTwo() {
        $coins = $this->lib->issueCoins('promise', 'public backer', 1, 1, 'issuer');
        $fractions = $this->lib->splitCoin($coins[0], [1, 2]);

        $this->assert->equals($fractions[0]->getFraction(), new Fraction(1, 3));
        $this->assert->equals($fractions[1]->getFraction(), new Fraction(2, 3));
    }

    function repeatedSplit() {
        $coins = $this->lib->issueCoins('promise', 'public backer', 1, 1, 'issuer');
        $once = $this->lib->splitCoin($coins[0], [2, 4]);
        $twice = $this->lib->splitCoin($once[0], [3, 4]);

        $this->assert->equals($twice[0]->getFraction(), new Fraction(6, 42));
        $this->assert->equals($twice[1]->getFraction(), new Fraction(8, 42));
    }

    function transferSplitCoin() {
        $coins = $this->lib->issueCoins('promise', 'public backer', 1, 1, 'issuer');
        list($half) = $this->lib->splitCoin($coins[0], [1, 1]);

        /** @var SplitCoin $first */
        $first = $this->lib->transferCoin($half, 'public first', 'backer');

        $this->assert->isInstanceOf($first, SplitCoin::class);
        $this->assert->equals($first->getFraction(), new Fraction(1, 2));
    }
}