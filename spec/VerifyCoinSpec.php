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
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'not issuer');
        $this->assert->not($this->lib->verifyCoin($coins[0], ['public issuer']));
    }

    function blankIssuerList() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'not issuer');
        $this->assert->isTrue($this->lib->verifyCoin($coins[0]));
    }

    function failIfNotTransferredByBacker() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $transferred = $this->lib->transferCoin($coins[0], 'public first', 'not backer');

        $this->assert->not($this->lib->verifyCoin($transferred, ['public issuer']));
    }

    function failIfIssuerSignatureIsInvalid() {
        $this->key->nextSign = 'wrong';
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');

        $this->assert->not($this->lib->verifyCoin($coins[0], ['public issuer']));
    }

    function failIfFirstSignatureIsInvalid() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $this->key->nextSign = 'wrong';
        $transferred = $this->lib->transferCoin($coins[0], 'public first', 'backer');

        $this->assert->not($this->lib->verifyCoin($transferred, ['public issuer']));
    }

    function failIfChainIsBroken() {
        $coins = $this->lib->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->lib->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->lib->transferCoin($first, 'public second', 'first');
        $third = $this->lib->transferCoin($second, 'public third', 'not second');

        $this->assert->not($this->lib->verifyCoin($third, ['public issuer']));
    }
}