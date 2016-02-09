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
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 42, 1);

        $transferred = $this->lib->transferCoin('backer', $coins[0], 'new owner');

        $this->assert->equals($transferred->getTransaction(), new Transference($coins[0], 'new owner'));
        $this->assert->equals($transferred->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($transferred, ['public issuer']));
    }

    function transferredCoin() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');

        $this->assert->equals($second->getTransaction(), new Transference($first, 'public second'));
        $this->assert->equals($second->getSignature()->getSigner(), 'public first');
        $this->assert->isTrue($this->lib->verifyCoin($second, ['public issuer']));
    }
}