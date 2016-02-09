<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Authorization;
use groupcash\php\model\Signature;
use groupcash\php\model\Signer;
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

    function success() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->assert->isTrue($this->lib->verifyCoin($coins[0], [
            Authorization::create('public issuer', new Signer(new FakeKeyService(), 'root'))
        ]));
    }

    function failIfIssuerIsNotAuthorized() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->assert->not($this->lib->verifyCoin($coins[0], [
            Authorization::create('not issuer', new Signer(new FakeKeyService(), 'root'))
        ]));
    }

    function failIfAuthorizationNotValid() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->assert->not($this->lib->verifyCoin($coins[0], [
            new Authorization('public issuer', new Signature('public root', 'invalid'))
        ]));
    }

    function failIfNotAuthorizedByCurrencyRoot() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->assert->not($this->lib->verifyCoin($coins[0], [
            Authorization::create('public issuer', new Signer(new FakeKeyService(), 'not root'))
        ]));
    }

    function skipIssuerVerification() {
        $coins = $this->lib->issueCoins('not issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->assert->isTrue($this->lib->verifyCoin($coins[0]));
    }

    function failIfNotTransferredByBacker() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $transferred = $this->lib->transferCoin('not backer', $coins[0], 'public first');

        $this->assert->not($this->lib->verifyCoin($transferred));
    }

    function failIfIssuerSignatureIsInvalid() {
        $this->key->nextSign = 'wrong';
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);

        $this->assert->not($this->lib->verifyCoin($coins[0]));
    }

    function failIfFirstSignatureIsInvalid() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->key->nextSign = 'wrong';
        $transferred = $this->lib->transferCoin('backer', $coins[0], 'public first');

        $this->assert->not($this->lib->verifyCoin($transferred));
    }

    function failIfChainIsBroken() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('not second', $second, 'public third');

        $this->assert->not($this->lib->verifyCoin($third));
    }
}