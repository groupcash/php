<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Transference;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * The owner of a coin transfers it by signing it together with the public key of the new owner. The transaction
 * must be validated by the backer of the coin to avoid double-spending.
 *
 * @property Assert assert <-
 * @property Groupcash lib
 */
class TransferCoinsSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function originalCoin() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');

        $transferred = $this->lib->transferCoin($coins[0], 'new owner', 'backer');

        $this->assert->equals($transferred->getTransaction(), new Transference($coins[0], 'new owner'));
        $this->assert->equals($transferred->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($transferred, ['public issuer']));
    }

    function transferredCoin() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');

        $this->assert->equals($second->getTransaction(), new Transference($first, 'public second'));
        $this->assert->equals($second->getSignature()->getSigner(), 'public first');
        $this->assert->isTrue($this->lib->verifyCoin($second, ['public issuer']));
    }
}