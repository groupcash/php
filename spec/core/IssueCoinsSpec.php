<?php
namespace spec\groupcash\php\core;

use groupcash\php\key\FakeFinger;
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
        $this->lib = new Groupcash(new FakeKeyService(), new FakeFinger());
    }

    function singleCoin() {
        $coin = $this->lib->issueCoin('issuer key', new Promise('foo', 'my promise'), new Output('backer', new Fraction(42)));

        /** @var Base $issue */
        $issue = $coin->getTransaction();

        $this->assert->isInstanceOf($issue, Base::class);
        $this->assert->equals($issue->getPromise(), new Promise('foo', 'my promise'));
        $this->assert->equals($issue->getInputs(), [new Promise('foo', 'my promise')]);
        $this->assert->equals($issue->getOutput(), new Output('backer', new Fraction(42)));
        $this->assert->equals($issue->getOutputs(), [new Output('backer', new Fraction(42))]);
        $this->assert->equals($issue->getSignature()->getSigner(), 'issuer');
        $this->assert->equals($issue->getSignature()->getSign(),
            serialize([
                [new Promise('foo', 'my promise')],
                [new Output('backer', new Fraction(42))]
            ]) . ' signed with issuer key');
    }
}