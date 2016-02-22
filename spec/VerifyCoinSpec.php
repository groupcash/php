<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Authorization;
use groupcash\php\model\Signature;
use groupcash\php\model\Signer;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * A coin is consistent if all signatures are valid, the transference chain is unbroken and it was issued
 * by a authorized issuer.
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
        $issuers = [$this->lib->authorizeIssuer('root', 'public issuer')];

        $this->assert->not($this->lib->findInconsistencies($coins[0], $issuers));
    }

    function failIfIssuerIsNotAuthorized() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $issuers = [$this->lib->authorizeIssuer('root', 'not issuer')];

        $this->assert->equals($this->lib->findInconsistencies($coins[0], $issuers),
            'The issuer is not authorized.');
    }

    function failIfAuthorizationNotValid() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $issuers = [new Authorization('public issuer', new Signature('public root', 'invalid'))];

        $this->assert->equals($this->lib->findInconsistencies($coins[0], $issuers),
            'The issuer is not authorized.');
    }

    function failIfNotAuthorizedByCurrencyRoot() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $issuers = [Authorization::create('public issuer', new Signer(new FakeKeyService(), 'not root'))];

        $this->assert->equals($this->lib->findInconsistencies($coins[0], $issuers),
            'The issuer is not authorized.');
    }

    function skipIssuerVerification() {
        $coins = $this->lib->issueCoins('not issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->assert->not($this->lib->findInconsistencies($coins[0]));
    }

    function failIfNotTransferredByBacker() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $transferred = $this->lib->transferCoin('not backer', $coins[0], 'public first');

        $this->assert->equals($this->lib->findInconsistencies($transferred),
            'Signed by non-owner [public not backer].');
    }

    function failIfIssuerSignatureIsInvalid() {
        $this->key->nextSign = 'wrong';
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);

        $this->assert->equals($this->lib->findInconsistencies($coins[0]),
            'Invalid signature by [public issuer].');
    }

    function failIfFirstSignatureIsInvalid() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $this->key->nextSign = 'wrong';
        $transferred = $this->lib->transferCoin('backer', $coins[0], 'public first');

        $this->assert->equals($this->lib->findInconsistencies($transferred),
            'Invalid signature by [public backer].');
    }

    function failIfChainIsBroken() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('not second', $second, 'public third');

        $this->assert->equals($this->lib->findInconsistencies($third),
            'Signed by non-owner [public not second].');
    }
}