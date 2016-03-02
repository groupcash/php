<?php
namespace spec\groupcash\php\core;

use groupcash\php\key\Binary;
use groupcash\php\key\FakeKeyService;
use groupcash\php\Groupcash;
use groupcash\php\model\Fraction;
use groupcash\php\model\Base;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use rtens\scrut\Assert;

/**
 * Coins are issued when an issuer transfers a Promise to a backer.
 *
 * @property Groupcash lib
 * @property Assert assert <-
 */
class IssueCoinsSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function singleCoin() {
        $coin = $this->lib->issueCoin(new Binary('issuer key'),new Promise(new Binary('foo'), 'my promise'), new Output(new Binary('backer'), new Fraction(42)));

        /** @var Base $base */
        $base = $coin->getInput()->getTransaction();

        $this->assert->isInstanceOf($base, Base::class);
        $this->assert->equals($base->getPromise(),new Promise(new Binary('foo'), 'my promise'));
        $this->assert->equals($base->getInputs(), []);
        $this->assert->equals($base->getOutput(), new Output(new Binary('backer'), new Fraction(42)));
        $this->assert->equals($base->getOutputs(), [new Output(new Binary('backer'), new Fraction(42))]);
        $this->assert->equals($base->getIssuerAddress(), new Binary('issuer'));
        $this->assert->equals($base->getSignature(),
            'foo' . "\0" . 'my promise' . "\0" . 'backer' . "\0" . '42|1' .
            ' signed with issuer key');
    }
}