<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
use groupcash\php\impl\EccKeyService;
use groupcash\php\impl\McryptCryptoService;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeCryptoService;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * A coin is a promise for a specific delivery of a certain commodity, signed by a regulating member of the group
 * together with the public key of the backer and a unique serial number.
 *
 * @property Assert assert <-
 */
class IssueCoinsSpec {

    function fake() {
        $app = new Application(new FakeKeyService('my key'), new FakeCryptoService());
        $privatePublic = $app->generateKey('foo');

        $coins = $app->issueCoins('my promise', 'public backer', 42, 3, $privatePublic['private'], 'foo');

        $this->assert->size($coins, 3);
        $this->assert->equals($app->decode($coins), [
            [
                'content' => [
                    'promise' => 'my promise',
                    'serial' => 42,
                    'backer' => 'public backer',
                ],
                'signer' => 'public my key',
                'signature' => '5aff16eda33e2f13591102378cc8dae0 signed with my key'
            ],
            [
                'content' => [
                    'promise' => 'my promise',
                    'serial' => 43,
                    'backer' => 'public backer',
                ],
                'signer' => 'public my key',
                'signature' => 'b6e6a67975ddc41ec33eddd34bad9847 signed with my key'
            ],
            [
                'content' => [
                    'promise' => 'my promise',
                    'serial' => 44,
                    'backer' => 'public backer',
                ],
                'signer' => 'public my key',
                'signature' => 'a1a80079245247b2ec34cafe8c351a18 signed with my key'
            ]
        ]);
    }

    function real() {
        if (!getenv('REAL')) {
            $this->assert->incomplete('Skipped. Set REAL environment variable to execute');
        }

        $app = new Application(new EccKeyService(), new McryptCryptoService());
        $keys = $app->generateKey('foo');
        $coins = $app->issueCoins('my promise', 'backer key', 1, 1, $keys['private'], 'foo');

        $this->assert->isTrue($app->verifySignature($coins[0]));
    }
}