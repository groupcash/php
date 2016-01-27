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
        $coins = $app->issueCoins('my promise', 'public backer', 42, 2, 'my key');

        $transferred = $app->transferCoins($coins, 'new owner', 'backer encrypted with foo', 'foo');
        $this->assert->equals($app->decode($transferred), [
            [
                'content' => [
                    'coin' => $coins[0],
                    'to' => 'new owner'
                ],
                'signer' => 'public backer',
                'signature' => '54eb4d54abd1c0a46f71a5aeefc976e2 signed with backer'
            ],
            [
                'content' => [
                    'coin' => $coins[1],
                    'to' => 'new owner'
                ],
                'signer' => 'public backer',
                'signature' => 'e72b3022e985815c33ccee91c69651f3 signed with backer'
            ]
        ]);
    }

    function transferredCoin() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());
        $coins = $app->issueCoins('my promise', 'public backer', 42, 2, 'my key');

        $transferred = $app->transferCoins($coins, 'public first', 'backer encrypted with foo', 'foo');

        $twice = $app->transferCoins($transferred, 'public second', 'first encrypted with foo', 'foo');

        $this->assert->equals($app->decode($twice), [
            [
                'content' => [
                    'coin' => $transferred[0],
                    'to' => 'public second'
                ],
                'signer' => 'public first',
                'signature' => 'cb3c612a8fff0e4539f000f8b03d2033 signed with first'
            ],
            [
                'content' => [
                    'coin' => $transferred[1],
                    'to' => 'public second'
                ],
                'signer' => 'public first',
                'signature' => 'f60a1fa9041f92125976fb29c8aefb64 signed with first'
            ]
        ]);
    }
}