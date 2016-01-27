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

        $coins = $app->issueCoins('my promise', 'backer key', 42, 3, $privatePublic['private'], 'foo');
        $this->assert->equals($coins, [
            '{"content":{"promise":"my promise","serial":42,"backer":"backer key","issuer":"public my key"},"signature":"{\\"promise\\":\\"my promise\\",\\"serial\\":42,\\"backer\\":\\"backer key\\",\\"issuer\\":\\"public my key\\"} signed with my key"}',
            '{"content":{"promise":"my promise","serial":43,"backer":"backer key","issuer":"public my key"},"signature":"{\\"promise\\":\\"my promise\\",\\"serial\\":43,\\"backer\\":\\"backer key\\",\\"issuer\\":\\"public my key\\"} signed with my key"}',
            '{"content":{"promise":"my promise","serial":44,"backer":"backer key","issuer":"public my key"},"signature":"{\\"promise\\":\\"my promise\\",\\"serial\\":44,\\"backer\\":\\"backer key\\",\\"issuer\\":\\"public my key\\"} signed with my key"}',
        ]);
    }

    function real() {
        $app = new Application(new EccKeyService(), new McryptCryptoService());
        $keys = $app->generateKey('foo');
        $coins = $app->issueCoins('my promise', 'backer key', 1, 1, $keys['private'], 'foo');

        $this->assert->isTrue($app->verifySignature($coins[0], $keys['public']));
    }
}