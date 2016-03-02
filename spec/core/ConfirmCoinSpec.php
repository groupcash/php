<?php
namespace spec\groupcash\php\core;

use groupcash\php\Groupcash;
use groupcash\php\key\FakeKeyService;
use groupcash\php\model\Confirmation;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * A backer confirms a coin by creating a new transaction with a proportional value.
 *
 * @property Groupcash lib
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class ConfirmCoinSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function notTheBacker() {
        $base = $this->lib->issueCoin('issuer key', new Promise('coin', 'I promise'), new Output('bart', new Fraction(1)));
        $one = $this->lib->transferCoins('bart key', [$base], [new Output('lisa', new Fraction(1))]);

        $this->try->tryTo(function () use ($one) {
            $this->lib->confirmCoin('not bart key', $one[0]);
        });
        $this->try->thenTheException_ShouldBeThrown('Not a backer');
    }

    function base() {
        $base = $this->lib->issueCoin('issuer key', new Promise('coin', 'I promise'), new Output('bart', new Fraction(1)));
        $confirmed = $this->lib->confirmCoin('bart key', $base);

        $this->assert->equals($confirmed->getOwner(), 'bart');
        $this->assert->equals($confirmed->getValue(), new Fraction(1));

        /** @var Confirmation $confirmation */
        $confirmation = $confirmed->getInput()->getTransaction();
        $this->assert->isInstanceOf($confirmation, Confirmation::class);
        $this->assert->equals($confirmation->getInputs(), [new Input($base->getInput()->getTransaction(), 0)]);
        $this->assert->equals($confirmation->getHash(), hash('sha256', 'coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1'));
        $this->assert->equals($confirmation->getSignature(),
            'coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1' . "\0" . 'bart' . "\0" . '1|1' . "\0" .
            hash('sha256', 'coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1') .
            ' signed with bart key');
    }

    function singleTransaction() {
        $base = $this->lib->issueCoin('issuer key', new Promise('coin', 'I promise'), new Output('bart', new Fraction(1)));
        $one = $this->lib->transferCoins('bart key', [$base], [new Output('lisa', new Fraction(1))]);

        $confirmed = $this->lib->confirmCoin('bart key', $one[0]);

        $this->assert->equals($confirmed->getOwner(), 'lisa');
        $this->assert->equals($confirmed->getValue(), new Fraction(1));

        /** @var Confirmation $confirmation */
        $confirmation = $confirmed->getInput()->getTransaction();
        $this->assert->isInstanceOf($confirmation, Confirmation::class);
        $this->assert->equals($confirmation->getHash(), hash('sha256', 'coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1' . "\0" . '0' . "\0" . 'lisa' . "\0" . '1|1'));
    }

    function chain() {
        $base = $this->lib->issueCoin('issuer key', new Promise('coin', 'I promise'), new Output('bart', new Fraction(1)));
        $one = $this->lib->transferCoins('bart key', [$base], [new Output('lisa', new Fraction(1))]);
        $two = $this->lib->transferCoins('lisa key', $one, [new Output('marge', new Fraction(1))]);
        $three = $this->lib->transferCoins('marge key', $two, [new Output('homer', new Fraction(1))]);

        $confirmed = $this->lib->confirmCoin('bart key', $three[0]);

        $this->assert->equals($confirmed->getOwner(), 'homer');
        $this->assert->isInstanceOf($confirmed->getInput()->getTransaction(), Confirmation::class);
        $this->assert->equals($confirmed->getInput()->getTransaction()->getInputs(),
            [new Input($base->getInput()->getTransaction(), 0)]);
    }

    function twoBases() {
        $one = $this->lib->issueCoin('issuer key', new Promise('coin', 'A'), new Output('bart', new Fraction(1)));
        $two = $this->lib->issueCoin('issuer key', new Promise('coin', 'B'), new Output('bart', new Fraction(2)));

        $transferred = $this->lib->transferCoins('bart key', [$one, $two], [new Output('lisa', new Fraction(3))]);
        $confirmed = $this->lib->confirmCoin('bart key', $transferred[0]);

        $this->assert->equals($confirmed->getOwner(), 'lisa');
        $this->assert->equals($confirmed->getValue(), new Fraction(3));
        $this->assert->equals($confirmed->getInput()->getTransaction()->getInputs(), [
            new Input($one->getInput()->getTransaction(), 0),
            new Input($two->getInput()->getTransaction(), 0)
        ]);
    }

    function differentBackers() {
        $bart = [
            $this->lib->issueCoin('issuer key', new Promise('coin', 'A'), new Output('bart', new Fraction(1))),
            $this->lib->issueCoin('issuer key', new Promise('coin', 'B'), new Output('bart', new Fraction(2)))
        ];
        $homer = [
            $this->lib->issueCoin('issuer key', new Promise('coin', 'C'), new Output('homer', new Fraction(5)))
        ];
        $lisa = array_merge(
            $this->lib->transferCoins('bart key', $bart, [new Output('lisa', new Fraction(3))]),
            $this->lib->transferCoins('homer key', $homer, [new Output('lisa', new Fraction(5))])
        );

        $marge = $this->lib->transferCoins('lisa key', $lisa, [new Output('marge', new Fraction(8))]);

        $bartConfirmed = $this->lib->confirmCoin('bart key', $marge[0]);
        $homerConfirmed = $this->lib->confirmCoin('homer key', $marge[0]);

        $this->assert->equals($bartConfirmed->getOwner(), 'marge');
        $this->assert->equals($bartConfirmed->getValue(), new Fraction(3));
        $this->assert->equals($homerConfirmed->getOwner(), 'marge');
        $this->assert->equals($homerConfirmed->getValue(), new Fraction(5));
    }

    function tree() {
        $a = $this->lib->issueCoin('i key', new Promise('c', 'p'), new Output('a', new Fraction(5)));
        $b = $this->lib->issueCoin('i key', new Promise('c', 'p'), new Output('b', new Fraction(7)));
        $c = $this->lib->issueCoin('i key', new Promise('c', 'p'), new Output('c', new Fraction(8)));

        $d = [
            $this->lib->transferCoins('a key', [$a], [
                new Output('d', new Fraction(4)),
                new Output('x', new Fraction(1))
            ])[0],
            $this->lib->transferCoins('b key', [$b], [
                new Output('d', new Fraction(2)),
                new Output('x', new Fraction(5))
            ])[0]
        ];

        $e = [
            $this->lib->transferCoins('c key', [$c], [
                new Output('e', new Fraction(7)),
                new Output('x', new Fraction(1))
            ])[0]
        ];

        $f = [
            $this->lib->transferCoins('d key', $d, [
                new Output('f', new Fraction(5)),
                new Output('x', new Fraction(1))
            ])[0],
            $this->lib->transferCoins('e key', $e, [
                new Output('f', new Fraction(4)),
                new Output('x', new Fraction(3)),
            ])[0]
        ];

        $g = $this->lib->transferCoins('f key', $f, [
            new Output('g', new Fraction(8)),
            new Output('x', new Fraction(1))
        ])[0];

        $confirmedA = $this->lib->confirmCoin('a key', $g);
        $confirmedB = $this->lib->confirmCoin('b key', $g);
        $confirmedC = $this->lib->confirmCoin('c key', $g);

        $this->assert->equals(
            $confirmedA->getValue()
            ->plus($confirmedB->getValue())
            ->plus($confirmedC->getValue()), $g->getValue());

        $this->assert->equals($confirmedA->getValue()->toFloat(), 2);
        $this->assert->equals($confirmedB->getValue()->toFloat(), 2.8);
        $this->assert->equals($confirmedC->getValue()->toFloat(), 3.2);
    }
}