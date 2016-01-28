<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
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
 * @property Application app
 */
class ValidateCoinSpec {

    function before() {
        $this->app = new Application(new FakeKeyService());
    }

    function doNotValidateOriginalCoin() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $validated = $this->app->validateTransference($coins[0], 'public first', 'backer');

        $this->assert->equals($validated, $coins[0]);
    }

    function failIfNotBacker() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');

        $this->try->tryTo(function () use ($coins) {
            $this->app->validateTransference($coins[0], 'public first', 'not backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Only the backer of a coin can validate it.');
    }

    function firstTransaction() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $transferred = $this->app->transferCoin($coins[0], 'public first', 'backer');

        $validated = $this->app->validateTransference($transferred, 'public first', 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public first', 'hash'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->app->verifyCoin($validated, ['public issuer']));
    }

    function secondTransaction() {
        $this->app = new Application(new FakeKeyService());

        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->app->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->app->transferCoin($first, 'public second', 'first');

        $validated = $this->app->validateTransference($second, 'public first', 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public second', 'hash'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->app->verifyCoin($validated, ['public issuer']));
    }

    function failIfWrongOwner() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->app->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->app->transferCoin($first, 'public second', 'first');

        $this->try->tryTo(function () use ($second) {
            $this->app->validateTransference($second, 'public not first', 'backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid transference.');
    }

    function thirdTransaction() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->app->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->app->transferCoin($first, 'public second', 'first');
        $third = $this->app->transferCoin($second, 'public third', 'second');

        $validated = $this->app->validateTransference($third, 'public first', 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public third', 'hash'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->app->verifyCoin($validated, ['public issuer']));
    }
}