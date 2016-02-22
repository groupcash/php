<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Fraction;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * The possibly multiple transactions of a coin since the last confirmation can be resolved to calculate how
 * the balance of each involved user changes.
 *
 * @property Assert assert <-
 * @property Groupcash lib
 */
class ResolveTransactionsSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function issuedCoin() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $balances = $this->lib->resolveTransactions($coins[0], 'public backer');

        $this->assert->equals($balances, []);
    }

    function validatedCoin() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $validated = $this->lib->confirmCoin('backer', $second);

        $balances = $this->lib->resolveTransactions($validated, 'public first');

        $this->assert->equals($balances, []);
    }

    function boughtCoin() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');

        $balances = $this->lib->resolveTransactions($first, 'public backer');

        $this->assert->equals($balances, [
            'public backer' => new Fraction(-1),
            'public first' => new Fraction(1)
        ]);
    }

    function singleTransference() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');

        $balances = $this->lib->resolveTransactions($second, 'public first');

        $this->assert->equals($balances, [
            'public first' => new Fraction(-1),
            'public second' => new Fraction(1)
        ]);
    }

    function multipleTransferences() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public third');

        $balances = $this->lib->resolveTransactions($third, 'public first');

        $this->assert->equals($balances, [
            'public first' => new Fraction(-1),
            'public second' => new Fraction(0),
            'public third' => new Fraction(1),
        ]);
    }

    function fromSecond() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public third');

        $balances = $this->lib->resolveTransactions($third, 'public second');

        $this->assert->equals($balances, [
            'public second' => new Fraction(-1),
            'public third' => new Fraction(1),
        ]);
    }

    function cancellingOut() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public first');

        $balances = $this->lib->resolveTransactions($third, 'public first');

        $this->assert->equals($balances, [
            'public first' => new Fraction(0),
            'public second' => new Fraction(0)
        ]);
    }

    function splitTransferences() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $sold = $this->lib->transferCoin('backer', $coins[0], 'public dude');

        $first = $this->lib->transferCoin('dude', $sold, 'public first', new Fraction(1, 3));
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public third', new Fraction(1, 2));

        $balances = $this->lib->resolveTransactions($third, 'public dude');

        $this->assert->equals($balances, [
            'public dude' => new Fraction(-1, 3),
            'public first' => new Fraction(0),
            'public second' => new Fraction(3, 18),
            'public third' => new Fraction(1, 6),
        ]);
    }

    function splitTransferencesWithDifferentStart() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 1, 1);
        $sold = $this->lib->transferCoin('backer', $coins[0], 'public dude');

        $first = $this->lib->transferCoin('dude', $sold, 'public first', new Fraction(1, 3));
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public third', new Fraction(1, 2));

        $balances = $this->lib->resolveTransactions($third, 'public first');

        $this->assert->equals($balances, [
            'public first' => new Fraction(-1, 3),
            'public second' => new Fraction(3, 18),
            'public third' => new Fraction(1, 6),
        ]);
    }
}