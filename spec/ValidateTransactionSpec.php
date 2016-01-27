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
                'to' => 'public first',
                'prev' => 'ac3ac68a2fdd56b0f2887bd5cb29c984fe5c59eb9fa34fa0273ddd0df552ea1e'
            ],
            'signer' => 'public backer',
            'signature' => '27353f33d6656ad00140ecb388b37412 signed with backer'
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
                'to' => 'public second',
                'prev' => 'fb9ed23d23f05cee8e75bf7dd1b45bcf5637447523e96c7c291f148cea120ca3'
            ],
            'signer' => 'public backer',
            'signature' => 'bf348668a52ffc2809578711f932fd2a signed with backer'
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
                'to' => 'public third',
                'prev' => '64bb925b3796ab044a946e586f54c0ff8fe85a5ec138b51275d7c35afbd1d088'
            ],
            'signer' => 'public backer',
            'signature' => '86660f808b475a4abeefe206734bd0ff signed with backer'
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