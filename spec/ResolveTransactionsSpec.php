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
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 1, 1);
        $balances = $this->lib->resolveTransactions($coins[0]);

        $this->assert->equals($balances, []);
    }

    function validatedCoin() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $validated = $this->lib->validateCoin('backer', $second);

        $balances = $this->lib->resolveTransactions($validated);

        $this->assert->equals($balances, []);
    }

    function singleTransference() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');

        $balances = $this->lib->resolveTransactions($second);

        $this->assert->equals($balances, [
            'public first' => new Fraction(-1),
            'public second' => new Fraction(1)
        ]);
    }

    function multipleTransferences() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public third');

        $balances = $this->lib->resolveTransactions($third);

        $this->assert->equals($balances, [
            'public first' => new Fraction(-1),
            'public second' => new Fraction(0),
            'public third' => new Fraction(1),
        ]);
    }

    function cancellingOut() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 1, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public first');

        $balances = $this->lib->resolveTransactions($third);

        $this->assert->equals($balances, [
            'public first' => new Fraction(0),
            'public second' => new Fraction(0)
        ]);
    }

    function splitTransferences() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 1, 1);
        $sold = $this->lib->transferCoin('backer', $coins[0], 'public dude');

        $first = $this->lib->transferCoin('dude', $sold, 'public first', new Fraction(1, 3));
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public third', new Fraction(1, 2));

        $balances = $this->lib->resolveTransactions($third);

        $this->assert->equals($balances, [
            'public dude' => new Fraction(-1, 3),
            'public first' => new Fraction(0),
            'public second' => new Fraction(1, 6),
            'public third' => new Fraction(1, 6),
        ]);
    }
}