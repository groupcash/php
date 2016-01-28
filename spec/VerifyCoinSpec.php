<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * A coin is verified by verifying all signatures, the transference chain and that it was issued by a known issuer.
 *
 * @property Assert assert <-
 * @property Application app
 * @property FakeKeyService key
 */
class VerifyCoinSpec {

    function before() {
        $this->key = new FakeKeyService();
        $this->app = new Application($this->key);
    }

    function failIfIssuerIsNotKnown() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'not issuer');
        $this->assert->not($this->app->verifyCoin($coins[0], ['public issuer']));
    }

    function blankIssuerList() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'not issuer');
        $this->assert->isTrue($this->app->verifyCoin($coins[0]));
    }

    function failIfNotTransferredByBacker() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $transferred = $this->app->transferCoin($coins[0], 'public first', 'not backer');

        $this->assert->not($this->app->verifyCoin($transferred, ['public issuer']));
    }

    function failIfIssuerSignatureIsInvalid() {
        $this->key->nextSign = 'wrong';
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');

        $this->assert->not($this->app->verifyCoin($coins[0], ['public issuer']));
    }

    function failIfFirstSignatureIsInvalid() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $this->key->nextSign = 'wrong';
        $transferred = $this->app->transferCoin($coins[0], 'public first', 'backer');

        $this->assert->not($this->app->verifyCoin($transferred, ['public issuer']));
    }

    function failIfChainIsBroken() {
        $coins = $this->app->issueCoins('my promise', 'public backer', 42, 1, 'issuer');
        $first = $this->app->transferCoin($coins[0], 'public first', 'backer');
        $second = $this->app->transferCoin($first, 'public second', 'first');
        $third = $this->app->transferCoin($second, 'public third', 'not second');

        $this->assert->not($this->app->verifyCoin($third, ['public issuer']));
    }
}