<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
use groupcash\php\model\Promise;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * A coin is a promise for a specific delivery of a certain commodity, signed by a regulating member of the group
 * together with the public key of the backer and a unique serial number.
 *
 * @property Assert assert <-
 */
class IssueCoinsSpec {

    function singleCoin() {
        $app = new Application(new FakeKeyService());
        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');

        $this->assert->size($coins, 1);

        $this->assert->equals($coins[0]->getTransaction(), new Promise('public backer', 'my promise', 42));
        $this->assert->equals($coins[0]->getSignature()->getSigner(), 'public issuer');
        $this->assert->isTrue($app->verifyCoin($coins[0], ['public issuer']));
    }

    function multipleCoins() {
        $app = new Application(new FakeKeyService());
        $coins = $app->issueCoins('my promise', 'public backer', 42, 3, 'issuer');

        $this->assert->size($coins, 3);

        $this->assert->equals($coins[0]->getTransaction(), new Promise('public backer', 'my promise', 42));
        $this->assert->equals($coins[1]->getTransaction(), new Promise('public backer', 'my promise', 43));
        $this->assert->equals($coins[2]->getTransaction(), new Promise('public backer', 'my promise', 44));
    }
}