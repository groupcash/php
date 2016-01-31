<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Fraction;
use groupcash\php\model\Transference;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * Each backer of a coin is responsible for validating its transactions to confirm the new owner and avoid
 * double-spending of a single coin. The new coin consist of the original coin, the public key of the new owner
 * and, if existing, a fingerprint of the previous coin.
 *
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 * @property Groupcash lib
 */
class ValidateCoinSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function issuedCoin() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $validated = $this->lib->validateCoin($coins[0], 'backer');

        $this->assert->equals($validated, $coins[0]);
    }

    function failIfNotBacker() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $transferred = $this->lib->transferCoin($coins[0], 'public first', 'backer');

        $this->try->tryTo(function () use ($transferred) {
            $this->lib->validateCoin($transferred, 'not backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Only the backer of a coin can validate it.');
    }

    function firstTransaction() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $transferred = $this->lib->transferCoin($coins[0], 'public first', 'backer');

        $validated = $this->lib->validateCoin($transferred, 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public first', new Fraction(1),
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . ')'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }

    function secondTransaction() {
        $this->lib = new Groupcash(new FakeKeyService());

        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');

        $validated = $this->lib->validateCoin($second, 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public second', new Fraction(1),
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . 'public first' . "\0" .
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . '))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }

    function thirdTransaction() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');
        $third = $this->lib->transferCoin($second, 'public third', 'second');

        $validated = $this->lib->validateCoin($third, 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public third', new Fraction(1),
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . 'public second' . "\0" .
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . 'public first' . "\0" .
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . ')))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }

    function transferenceAfterValidation() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');

        $validatedSecond = $this->lib->validateCoin($second, 'backer');
        $third = $this->lib->transferCoin($validatedSecond, 'public third', 'second');

        $validated = $this->lib->validateCoin($third, 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public third', new Fraction(1),
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . 'public second' . "\0" .
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . '))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }

    function fractions() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer', new Fraction(1, 2));
        $second = $this->lib->transferCoin($first, 'public second', 'first', new Fraction(1, 3));
        $third = $this->lib->transferCoin($second, 'public third', 'second', new Fraction(5, 7));

        $validated = $this->lib->validateCoin($third, 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public third', new Fraction(5, 42),
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . 'public second' . "\0" .
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . 'public first' . "\0" .
            '(my promise' . "\0" . '42' . "\0" . 'public backer' . "\0" . ')))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }
}