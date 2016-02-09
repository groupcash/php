<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * A coin is verified by verifying all signatures, the transference chain and that it was issued by a known issuer.
 *
 * @property Assert assert <-
 * @property FakeKeyService key
 * @property Groupcash lib
 */
class VerifyCoinSpec {

    function before() {
        $this->key = new FakeKeyService();
        $this->lib = new Groupcash($this->key);
    }

    function failIfIssuerIsNotKnown() {
        $coins = $this->lib->issueCoins('not issuer', 'my promise', 'public backer', 42, 1);
        $this->assert->not($this->lib->verifyCoin($coins[0], ['public issuer']));
    }

    function blankIssuerList() {
        $coins = $this->lib->issueCoins('not issuer', 'my promise', 'public backer', 42, 1);
        $this->assert->isTrue($this->lib->verifyCoin($coins[0]));
    }

    function failIfNotTransferredByBacker() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 42, 1);
        $transferred = $this->lib->transferCoin('not backer', $coins[0], 'public first');

        $this->assert->not($this->lib->verifyCoin($transferred, ['public issuer']));
    }

    function failIfIssuerSignatureIsInvalid() {
        $this->key->nextSign = 'wrong';
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 42, 1);

        $this->assert->not($this->lib->verifyCoin($coins[0], ['public issuer']));
    }

    function failIfFirstSignatureIsInvalid() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 42, 1);
        $this->key->nextSign = 'wrong';
        $transferred = $this->lib->transferCoin('backer', $coins[0], 'public first');

        $this->assert->not($this->lib->verifyCoin($transferred, ['public issuer']));
    }

    function failIfChainIsBroken() {
        $coins = $this->lib->issueCoins('issuer', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('not second', $second, 'public third');

        $this->assert->not($this->lib->verifyCoin($third, ['public issuer']));
    }
}