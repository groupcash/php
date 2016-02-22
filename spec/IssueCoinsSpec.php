<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Promise;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * A coin represents a delivery promise made by a backer, identified by a serial number
 * and signed by the regulator of the currency.
 *
 * @property Assert assert <-
 * @property Groupcash lib
 */
class IssueCoinsSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function singleCoin() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);

        $this->assert->size($coins, 1);

        $this->assert->equals($coins[0]->getTransaction(), new Promise('public root', 'public backer', 'my promise', 42));
        $this->assert->equals($coins[0]->getSignature()->getSigner(), 'public issuer');
        $this->assert->not($this->lib->findInconsistencies($coins[0]));
    }

    function multipleCoins() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 3);

        $this->assert->size($coins, 3);

        $this->assert->equals($coins[0]->getTransaction(), new Promise('public root', 'public backer', 'my promise', 42));
        $this->assert->equals($coins[1]->getTransaction(), new Promise('public root', 'public backer', 'my promise', 43));
        $this->assert->equals($coins[2]->getTransaction(), new Promise('public root', 'public backer', 'my promise', 44));
    }
}