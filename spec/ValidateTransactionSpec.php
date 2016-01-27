<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;
use spec\groupcash\php\fakes\FakeCryptoService;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * Each backer of a coin is responsible for validating its transactions to confirm the new owner and avoid
 * double-spending of a single coin. The new coin consist of the original coin, the public key of the new owner
 * and, if existing, a fingerprint of the previous coin.
 *
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class ValidateTransactionSpec {

    function firstTransaction() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $transferred = $app->transferCoin($coins[0], 'public first', 'backer');

        $validated = $app->validateTransaction($transferred, 'public first', 'backer encrypted with foo', 'foo');
        $this->assert->equals($validated, [
            'content' => [
                'coin' => $coins[0],
                'owner' => 'public first',
                'prev' => 'a38ea3096857b49949c114971513bb7189aa9d1986147a64f1912ee5dc22f007'
            ],
            'signer' => 'public backer',
            'signature' => '6dd34c53acfcc2dffdb361906e17bf3e signed with backer'
        ]);
    }

    function failIfNotTransferredByBacker() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $transferred = $app->transferCoin($coins[0], 'public first', 'not backer');

        $this->try->tryTo(function () use ($app, $transferred) {
            $app->validateTransaction($transferred, 'public first', 'backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid validation.');
    }

    function failIfNotBacker() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $transferred = $app->transferCoin($coins[0], 'public first', 'backer');

        $this->try->tryTo(function () use ($app, $transferred) {
            $app->validateTransaction($transferred, 'public first', 'not backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function failIfAdminSignatureIsInvalid() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $coins[0]['signer'] = 'other';
        $transferred = $app->transferCoin($coins[0], 'public first', 'backer');

        $this->try->tryTo(function () use ($app, $transferred) {
            $app->validateTransaction($transferred, 'public first', 'backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid coin.');
    }

    function failIfFirstSignatureIsInvalid() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $transferred = $app->transferCoin($coins[0], 'public first', 'backer');
        $transferred['signer'] = 'other';

        $this->try->tryTo(function () use ($app, $transferred) {
            $app->validateTransaction($transferred, 'public first', 'backer');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid signature.');
    }

    function secondTransaction() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $first = $app->transferCoin($coins[0], 'public first', 'backer');
        $second = $app->transferCoin($first, 'public second', 'first');

        $validated = $app->validateTransaction($second, 'public first', 'backer encrypted with foo', 'foo');
        $this->assert->equals($validated, [
            'content' => [
                'coin' => $coins[0],
                'owner' => 'public second',
                'prev' => 'e18a2130819a0a4dc79d3eff80dc4f55131194518dac8f34566e803c90d9aaa9'
            ],
            'signer' => 'public backer',
            'signature' => '740281a7078d11f90564b44ffda47d74 signed with backer'
        ]);
    }

    function failIfWrongOwner() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $first = $app->transferCoin($coins[0], 'public first', 'backer');
        $second = $app->transferCoin($first, 'public second', 'first');

        $this->try->tryTo(function () use ($app, $second) {
            $app->validateTransaction($second, 'public not first', 'backer encrypted with foo', 'foo');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid transaction.');
    }


    function failIfSecondSignatureIsInvalid() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $first = $app->transferCoin($coins[0], 'public first', 'backer');
        $second = $app->transferCoin($first, 'public second', 'first');

        $second['signer'] = 'other';

        $this->try->tryTo(function () use ($app, $second) {
            $app->validateTransaction($second, 'public first', 'backer encrypted with foo', 'foo');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid signature.');
    }

    function thirdTransaction() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $first = $app->transferCoin($coins[0], 'public first', 'backer');
        $second = $app->transferCoin($first, 'public second', 'first');
        $third = $app->transferCoin($second, 'public third', 'second');

        $validated = $app->validateTransaction($third, 'public first', 'backer encrypted with foo', 'foo');
        $this->assert->equals($validated, [
            'content' => [
                'coin' => $coins[0],
                'owner' => 'public third',
                'prev' => 'a51483ee09d618700ffc86c56f388dcb48a9b971927e223af75873841523c2d3'
            ],
            'signer' => 'public backer',
            'signature' => 'be91202e0df56fb59523b40752827bac signed with backer'
        ]);
    }

    function failIfChainIsBroken() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $first = $app->transferCoin($coins[0], 'public first', 'backer');
        $second = $app->transferCoin($first, 'public second', 'first');
        $third = $app->transferCoin($second, 'public third', 'other');

        $this->try->tryTo(function () use ($app, $third) {
            $app->validateTransaction($third, 'public first', 'backer encrypted with foo', 'foo');
        });
        $this->try->thenTheException_ShouldBeThrown('Broken transaction.');
    }
}