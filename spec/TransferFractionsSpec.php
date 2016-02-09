<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Fraction;
use groupcash\php\model\Transference;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * In order to support micro-payments, fractions of coins can be transferred. For validation, the fraction that each
 * owner is entitled to needs to be kept track of.
 *
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 * @property Groupcash lib
 */
class TransferFractionsSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function none() {
        $coins = $this->lib->issueCoins('issuer', 'promise', 'public backer', 1, 1);
        $coin = $this->lib->transferCoin('backer', $coins[0], 'public first', new Fraction(0));

        $this->assert->equals($coin->getTransaction(),
            new Transference($coins[0], 'public first', new Fraction(0)));
    }

    function all() {
        $coins = $this->lib->issueCoins('issuer', 'promise', 'public backer', 1, 1);
        $coin = $this->lib->transferCoin('backer', $coins[0], 'public first', new Fraction(1));

        $this->assert->equals($coin->getTransaction(),
            new Transference($coins[0], 'public first', new Fraction(1)));
    }

    function half() {
        $coins = $this->lib->issueCoins('issuer', 'promise', 'public backer', 1, 1);
        $coin = $this->lib->transferCoin('backer', $coins[0], 'public first', new Fraction(1, 2));

        $this->assert->equals($coin->getTransaction(),
            new Transference($coins[0], 'public first', new Fraction(1, 2)));
    }

    function thirdOfHalf() {
        $coins = $this->lib->issueCoins('issuer', 'promise', 'public backer', 1, 1);
        $half = $this->lib->transferCoin('backer', $coins[0], 'public first', new Fraction(1, 2));
        $sixth = $this->lib->transferCoin('backer', $half, 'public first', new Fraction(1, 3));

        $this->assert->equals($sixth->getTransaction(),
            new Transference($half, 'public first', new Fraction(1, 3)));
    }
}