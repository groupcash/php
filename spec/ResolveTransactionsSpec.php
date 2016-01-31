<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Fraction;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * The possibly multiple transactions of a coin since the last validation can be resolved to calculate how the balance
 * of each involved member changes. The coin should only be validated if the total balance of no member goes below zero.
 *
 * @property Assert assert <-
 * @property Groupcash lib
 */
class ResolveTransactionsSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function issuedCoin() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 1, 1, 'issuer');
        $balances = $this->lib->resolveTransactions($coins[0]);

        $this->assert->equals($balances, []);
    }

    function validatedCoin() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 1, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');
        $validated = $this->lib->validateCoin($second, 'backer');

        $balances = $this->lib->resolveTransactions($validated);

        $this->assert->equals($balances, []);
    }

    function singleTransference() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 1, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');

        $balances = $this->lib->resolveTransactions($second);

        $this->assert->equals($balances, [
            'public first' => new Fraction(-1),
            'public second' => new Fraction(1)
        ]);
    }

    function multipleTransferences() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 1, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');
        $third = $this->lib->transferCoin($second, 'public third', 'second');

        $balances = $this->lib->resolveTransactions($third);

        $this->assert->equals($balances, [
            'public first' => new Fraction(-1),
            'public second' => new Fraction(0),
            'public third' => new Fraction(1),
        ]);
    }

    function cancellingOut() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 1, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');
        $third = $this->lib->transferCoin($second, 'public first', 'second');

        $balances = $this->lib->resolveTransactions($third);

        $this->assert->equals($balances, [
            'public first' => new Fraction(0),
            'public second' => new Fraction(0)
        ]);
    }

    function splitTransferences() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 1, 1, 'issuer');
        $sold = $this->lib->transferCoin($coins[0], 'public dude', 'backer');

        $first = $this->lib->transferCoin($sold, 'public first', 'dude', new Fraction(1, 3));
        $second = $this->lib->transferCoin($first, 'public second', 'first');
        $third = $this->lib->transferCoin($second, 'public third', 'second', new Fraction(1, 2));

        $balances = $this->lib->resolveTransactions($third);

        $this->assert->equals($balances, [
            'public dude' => new Fraction(-1, 3),
            'public first' => new Fraction(0),
            'public second' => new Fraction(1, 6),
            'public third' => new Fraction(1, 6),
        ]);
    }
}