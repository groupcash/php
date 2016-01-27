<?php
namespace spec\groupcash\php;
use groupcash\php\Application;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeCryptoService;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * The owner of a coin transfers it by signing it together with the public key of the new owner. The transaction
 * must be validated by the backer of the coin to avoid double-spending.
 *
 * @property Assert assert <-
 */
class TransferCoinsSpec {

    function originalCoin() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());
        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'my key');

        $transferred = $app->transferCoin($coins[0], 'new owner', 'backer encrypted with foo', 'foo');
        $this->assert->equals($transferred, [
            'content' => [
                'coin' => $coins[0],
                'owner' => 'new owner'
            ],
            'signer' => 'public backer',
            'signature' => 'b86f3e35332e07fba465d5d386c2e3ac signed with backer'
        ]);
    }

    function transferredCoin() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'my key');
        $transferred = $app->transferCoin($coins[0], 'public first', 'backer encrypted with foo', 'foo');
        $twice = $app->transferCoin($transferred, 'public second', 'first encrypted with foo', 'foo');

        $this->assert->equals($twice, [
            'content' => [
                'coin' => $transferred,
                'owner' => 'public second'
            ],
            'signer' => 'public first',
            'signature' => '9e57cad83cc4d6db69d4639b16b07653 signed with first'
        ]);
    }
}