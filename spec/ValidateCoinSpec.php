<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
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

    function doNotValidateOriginalCoin() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $validated = $this->lib->validateTransference($coins[0], 'public first', 'backer');

        $this->assert->equals($validated, $coins[0]);
    }

    function failIfNotBacker() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');

        $this->try->tryTo(function () use ($coins) {
            $this->lib->validateTransference($coins[0], 'public first', 'not backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Only the backer of a coin can validate it.');
    }

    function firstTransaction() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $transferred = $this->lib->transferCoin($coins[0], 'public first', 'backer');

        $validated = $this->lib->validateTransference($transferred, 'public first', 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public first', 'hash'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }

    function secondTransaction() {
        $this->lib = new Groupcash(new FakeKeyService());

        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');

        $validated = $this->lib->validateTransference($second, 'public first', 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public second', 'hash'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }

    function failIfWrongOwner() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');

        $this->try->tryTo(function () use ($second) {
            $this->lib->validateTransference($second, 'public not first', 'backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid transference.');
    }

    function thirdTransaction() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');
        $third = $this->lib->transferCoin($second, 'public third', 'second');

        $validated = $this->lib->validateTransference($third, 'public first', 'backer');

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public third', 'hash'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated, ['public issuer']));
    }
}