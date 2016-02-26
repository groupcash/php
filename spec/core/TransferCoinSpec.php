<?php
namespace spec\groupcash\php\core;

use groupcash\php\Groupcash;
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
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function noCoins() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('a key', [
                // Empty
            ], [
                new Output('', new Fraction(0))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('No inputs');
    }

    function emptyOutput() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('a key', [
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('a', new Fraction(1))),
            ], [
                new Output('', new Fraction(0)),
                new Output('', new Fraction(1)),
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Zero output value');
    }

    function negativeOutput() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('a key', [
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('a', new Fraction(1))),
            ], [
                new Output('', new Fraction(-1)),
                new Output('', new Fraction(2)),
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Negative output value');
    }

    function outputOverflow() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('a key', [
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('a', new Fraction(3))),
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('a', new Fraction(2))),
            ], [
                new Output('', new Fraction(2)),
                new Output('', new Fraction(4))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Output sum greater than input sum');
    }

    function outputUnderflow() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('a key', [
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('a', new Fraction(3))),
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('a', new Fraction(2))),
            ], [
                new Output('', new Fraction(1)),
                new Output('', new Fraction(3))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Output sum less than input sum');
    }

    function differentOwners() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('a key', [
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('a', new Fraction(1))),
                $this->lib->issueCoin('i key', new Promise('', ''), new Output('b', new Fraction(1))),
            ], [
                new Output('', new Fraction(2))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Inconsistent owners: [a], [b]');
    }

    function differentCurrencies() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('a key', [
                $this->lib->issueCoin('i key', new Promise('a', ''), new Output('a', new Fraction(1))),
                $this->lib->issueCoin('i key', new Promise('b', ''), new Output('a', new Fraction(1))),
            ], [
                new Output('', new Fraction(2))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Inconsistent currencies: [a], [b]');
    }

    function wrongKey() {
        $this->try->tryTo(function () {
            $this->lib->transferCoins('not a key', [
                $this->lib->issueCoin('i key', new Promise('', '1'), new Output('a', new Fraction(1))),
                $this->lib->issueCoin('i key', new Promise('', '2'), new Output('a', new Fraction(1))),
            ], [
                new Output('b key', new Fraction(2))
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Signed by non-owner: [not a]');
    }

    function noOutput() {
        $transferred = $this->lib->transferCoins('', [
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('', new Fraction(1))),
        ], [
            // Empty
        ]);
        $this->assert->size($transferred, 0);
    }

    function base() {
        $base = $this->lib->issueCoin('i key', new Promise('c', 'p'), new Output('bart', new Fraction(1)));
        $transferred = $this->lib->transferCoins('bart key', [
            $base
        ], [
            new Output('lisa', new Fraction(1))
        ]);

        $this->assert->size($transferred, 1);
        $this->assert->equals($transferred[0]->getOwner(), 'lisa');
        $this->assert->equals($transferred[0]->getValue(), new Fraction(1));

        $input = $transferred[0]->getInput();
        $this->assert->equals($input->getOutput(), new Output('lisa', new Fraction(1)));

        $inputTx = $input->getTransaction();
        $this->assert->equals($inputTx->getInputs(), [$base->getInput()]);
        $this->assert->equals($inputTx->getOutputs(), [new Output('lisa', new Fraction(1))]);
        $this->assert->equals($inputTx->getSignature()->getSigner(), 'bart');
        $this->assert->equals($inputTx->getSignature()->getSign(),
            '#(c' . "\0" . 'p' . "\0" . 'bart' . "\0" . '1|1' . "\0" . '0' . "\0" . 'lisa' . "\0" . '1|1)' .
            ' signed with bart key');
    }

    function multipleOutputs() {
        $transferred = $this->lib->transferCoins('bart key', [
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(6)))
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
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(1))),
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(2))),
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(3)))
        ], [
            new Output('lisa', new Fraction(6))
        ]);

        $this->assert->size($transferred, 1);
        $this->assert->equals($transferred[0]->getOwner(), 'lisa');
        $this->assert->equals($transferred[0]->getValue(), new Fraction(6));
    }

    function multipleInputsAndOutputs() {
        $transferred = $this->lib->transferCoins('bart key', [
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(1))),
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(2))),
            $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(3)))
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
        $coin = $this->lib->issueCoin('i key', new Promise('', ''), new Output('bart', new Fraction(1)));
        $one = $this->lib->transferCoins('bart key', [$coin], [new Output('lisa', new Fraction(1))]);
        $two = $this->lib->transferCoins('lisa key', [$one[0]], [new Output('marge', new Fraction(1))]);
        $three = $this->lib->transferCoins('marge key', [$two[0]], [new Output('homer', new Fraction(1))]);

        $this->assert->size($three, 1);
        $this->assert->equals($three[0]->getOwner(), 'homer');

        $tx = $three[0]->getInput()->getTransaction();
        $this->assert->equals($tx->getInputs(), [$two[0]->getInput()]);
        $this->assert->equals($tx->getInputs()[0]->getTransaction()->getInputs(), [$one[0]->getInput()]);
    }
}