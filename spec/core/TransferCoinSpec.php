<?php
namespace spec\groupcash\php\core;

use groupcash\php\Groupcash;
use groupcash\php\key\FakeFinger;
use groupcash\php\key\FakeKeyService;
use groupcash\php\model\Fraction;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * Coins are transferred by creating a new Transaction with its Output as Input.
 *
 * The sum of Output values must equal the sum of Input values.
 *
 * @property Groupcash lib
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class TransferCoinSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService(), new FakeFinger());
    }

    function noCoins() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                // Empty
            ], [
                // Empty
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('No coins given.');
    }

    function noOutput() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                $this->lib->issueCoin('', new Promise('', ''), new Output('', new Fraction(1))),
            ], [
                // Empty
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('No outputs given.');
    }

    function emptyOutput() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                $this->lib->issueCoin('', new Promise('', ''), new Output('', new Fraction(1))),
            ], [
                new Output('', new Fraction(0))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Output values must be positive.');
    }

    function negativeOutput() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                $this->lib->issueCoin('', new Promise('', ''), new Output('', new Fraction(1))),
            ], [
                new Output('', new Fraction(-1))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Output values must be positive.');
    }

    function outputOverflow() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                $this->lib->issueCoin('', new Promise('', ''), new Output('', new Fraction(3))),
                $this->lib->issueCoin('', new Promise('', ''), new Output('', new Fraction(2))),
            ], [
                new Output('', new Fraction(2)),
                new Output('', new Fraction(4))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('The output value must equal the input value.');
    }

    function outputUnderflow() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                $this->lib->issueCoin('', new Promise('', ''), new Output('', new Fraction(3))),
                $this->lib->issueCoin('', new Promise('', ''), new Output('', new Fraction(2))),
            ], [
                new Output('', new Fraction(1)),
                new Output('', new Fraction(3))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('The output value must equal the input value.');
    }

    function notSameOwner() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                $this->lib->issueCoin('', new Promise('', ''), new Output('a', new Fraction(1))),
                $this->lib->issueCoin('', new Promise('', ''), new Output('b', new Fraction(1))),
            ], [
                new Output('', new Fraction(2))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('All coins must have the same owner.');
    }

    function wrongKey() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('', [
                $this->lib->issueCoin('', new Promise('', ''), new Output('a', new Fraction(1))),
                $this->lib->issueCoin('', new Promise('', ''), new Output('a', new Fraction(1))),
            ], [
                new Output('b key', new Fraction(2))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Only the owner can transfer coins.');
    }

    function base() {
        $transferred = $this->lib->transferCoins('bart key', [
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(1)))
        ], [
            new Output('lisa', new Fraction(1))
        ]);

        $this->assert->size($transferred, 1);
        $this->assert->equals($transferred[0]->getOwner(), 'lisa');
        $this->assert->equals($transferred[0]->getValue(), new Fraction(1));
        $this->assert->equals($transferred[0]->getOutput(), new Output('lisa', new Fraction(1)));
        $this->assert->equals($transferred[0]->getTransaction()->getInputs(), [$this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(1)))]);
        $this->assert->equals($transferred[0]->getTransaction()->getOutputs(), [new Output('lisa', new Fraction(1))]);
        $this->assert->equals($transferred[0]->getTransaction()->getSignature()->getSigner(), 'bart');
        $this->assert->equals($transferred[0]->getTransaction()->getSignature()->getSign(),
            serialize([[$this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(1)))], [new Output('lisa', new Fraction(1))]]) . ' signed with bart key');
    }

    function multipleOutputs() {
        $transferred = $this->lib->transferCoins('bart key', [
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(6)))
        ], [
            new Output('lisa', new Fraction(1)),
            new Output('marge', new Fraction(2)),
            new Output('homer', new Fraction(3)),
        ]);

        $this->assert->size($transferred, 3);
        $this->assert->equals($transferred[0]->getOwner(), 'lisa');
        $this->assert->equals($transferred[1]->getOwner(), 'marge');
        $this->assert->equals($transferred[2]->getOwner(), 'homer');
        $this->assert->equals($transferred[0]->getValue(), new Fraction(1));
        $this->assert->equals($transferred[1]->getValue(), new Fraction(2));
        $this->assert->equals($transferred[2]->getValue(), new Fraction(3));
    }

    function multipleInputs() {
        $transferred = $this->lib->transferCoins('bart key', [
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(1))),
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(2))),
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(3)))
        ], [
            new Output('lisa', new Fraction(6))
        ]);

        $this->assert->size($transferred, 1);
        $this->assert->equals($transferred[0]->getOwner(), 'lisa');
        $this->assert->equals($transferred[0]->getValue(), new Fraction(6));
    }

    function multipleInputsAndOutputs() {
        $transferred = $this->lib->transferCoins('bart key', [
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(1))),
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(2))),
            $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(3)))
        ], [
            new Output('lisa', new Fraction(3)),
            new Output('homer', new Fraction(1)),
            new Output('bart', new Fraction(2)),
        ]);

        $this->assert->size($transferred, 3);
        $this->assert->equals($transferred[0]->getOwner(), 'lisa');
        $this->assert->equals($transferred[0]->getValue(), new Fraction(3));
        $this->assert->equals($transferred[1]->getOwner(), 'homer');
        $this->assert->equals($transferred[1]->getValue(), new Fraction(1));
        $this->assert->equals($transferred[2]->getOwner(), 'bart');
        $this->assert->equals($transferred[2]->getValue(), new Fraction(2));
    }

    function chaining() {
        $coin = $this->lib->issueCoin('', new Promise('', ''), new Output('bart', new Fraction(1)));
        $one = $this->lib->transferCoins('bart key', [$coin], [new Output('lisa', new Fraction(1))]);
        $two = $this->lib->transferCoins('lisa key', [$one[0]], [new Output('marge', new Fraction(1))]);
        $three = $this->lib->transferCoins('marge key', [$two[0]], [new Output('homer', new Fraction(1))]);

        $this->assert->size($three, 1);
        $this->assert->equals($three[0]->getOwner(), 'homer');
        $this->assert->equals($three[0]->getTransaction()->getInputs(), [$two[0]]);
        $this->assert->equals($three[0]->getTransaction()->getInputs()[0]->getTransaction()->getInputs(), [$one[0]]);
    }
}