<?php
namespace spec\groupcash\php\core;

use groupcash\php\Groupcash;
use groupcash\php\model\signing\Binary;
use groupcash\php\key\FakeKeyService;
use groupcash\php\model\Confirmation;
use groupcash\php\model\value\Fraction;
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
        $base = $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'I promise'), new Output(new Binary('bart'), new Fraction(1)));
        $one = $this->lib->transferCoins(new Binary('bart key'), [$base], [new Output(new Binary('lisa'), new Fraction(1))]);

        $this->try->tryTo(function () use ($one) {
            $this->lib->confirmCoin(new Binary('not bart key'), $one[0]);
        });
        $this->try->thenTheException_ShouldBeThrown('Not a backer');
    }

    function base() {
        $base = $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'I promise'), new Output(new Binary('bart'), new Fraction(1)));
        $confirmed = $this->lib->confirmCoin(new Binary('bart key'), $base);

        $this->assert->equals($confirmed->getOwner(), new Binary('bart'));
        $this->assert->equals($confirmed->getValue(), new Fraction(1));

        /** @var Confirmation $confirmation */
        $confirmation = $confirmed->getInput()->getTransaction();
        $this->assert->isInstanceOf($confirmation, Confirmation::class);
        $this->assert->equals($confirmation->getInputs(), [new Input($base->getInput()->getTransaction(), 0)]);
        $this->assert->equals($confirmation->getHash(),  Confirmation::hash('coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1'));
        $this->assert->equals($confirmation->getSignature(),
            'coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1' . "\0" . 'bart' . "\0" . '1|1' . "\0" .
            Confirmation::hash('coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1') .
            ' signed with bart key');
    }

    function singleTransaction() {
        $base = $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'I promise'), new Output(new Binary('bart'), new Fraction(1)));
        $one = $this->lib->transferCoins(new Binary('bart key'), [$base], [new Output(new Binary('lisa'), new Fraction(1))]);

        $confirmed = $this->lib->confirmCoin(new Binary('bart key'), $one[0]);

        $this->assert->equals($confirmed->getOwner(), new Binary('lisa'));
        $this->assert->equals($confirmed->getValue(), new Fraction(1));

        /** @var Confirmation $confirmation */
        $confirmation = $confirmed->getInput()->getTransaction();
        $this->assert->isInstanceOf($confirmation, Confirmation::class);
        $this->assert->equals($confirmation->getHash(), Confirmation::hash('coin' . "\0" . 'I promise' . "\0" . 'bart' . "\0" . '1|1' . "\0" . '0' . "\0" . 'lisa' . "\0" . '1|1'));
    }

    function chain() {
        $base = $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'I promise'), new Output(new Binary('bart'), new Fraction(1)));
        $one = $this->lib->transferCoins(new Binary('bart key'), [$base], [new Output(new Binary('lisa'), new Fraction(1))]);
        $two = $this->lib->transferCoins(new Binary('lisa key'), $one, [new Output(new Binary('marge'), new Fraction(1))]);
        $three = $this->lib->transferCoins(new Binary('marge key'), $two, [new Output(new Binary('homer'), new Fraction(1))]);

        $confirmed = $this->lib->confirmCoin(new Binary('bart key'), $three[0]);

        $this->assert->equals($confirmed->getOwner(), new Binary('homer'));
        $this->assert->isInstanceOf($confirmed->getInput()->getTransaction(), Confirmation::class);
        $this->assert->equals($confirmed->getInput()->getTransaction()->getInputs(),
            [new Input($base->getInput()->getTransaction(), 0)]);
    }

    function twoBases() {
        $one = $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'A'), new Output(new Binary('bart'), new Fraction(1)));
        $two = $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'B'), new Output(new Binary('bart'), new Fraction(2)));

        $transferred = $this->lib->transferCoins(new Binary('bart key'), [$one, $two], [new Output(new Binary('lisa'), new Fraction(3))]);
        $confirmed = $this->lib->confirmCoin(new Binary('bart key'), $transferred[0]);

        $this->assert->equals($confirmed->getOwner(), new Binary('lisa'));
        $this->assert->equals($confirmed->getValue(), new Fraction(3));
        $this->assert->equals($confirmed->getInput()->getTransaction()->getInputs(), [
            new Input($one->getInput()->getTransaction(), 0),
            new Input($two->getInput()->getTransaction(), 0)
        ]);
    }

    function differentBackers() {
        $bart = [
            $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'A'), new Output(new Binary('bart'), new Fraction(1))),
            $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'B'), new Output(new Binary('bart'), new Fraction(2)))
        ];
        $homer = [
            $this->lib->issueCoin(new Binary('issuer key'), new Promise(new Binary('coin'), 'C'), new Output(new Binary('homer'), new Fraction(5)))
        ];
        $lisa = array_merge(
            $this->lib->transferCoins(new Binary('bart key'), $bart, [new Output(new Binary('lisa'), new Fraction(3))]),
            $this->lib->transferCoins(new Binary('homer key'), $homer, [new Output(new Binary('lisa'), new Fraction(5))])
        );

        $marge = $this->lib->transferCoins(new Binary('lisa key'), $lisa, [new Output(new Binary('marge'), new Fraction(8))]);

        $bartConfirmed = $this->lib->confirmCoin(new Binary('bart key'), $marge[0]);
        $homerConfirmed = $this->lib->confirmCoin(new Binary('homer key'), $marge[0]);

        $this->assert->equals($bartConfirmed->getOwner(), new Binary('marge'));
        $this->assert->equals($bartConfirmed->getValue(), new Fraction(3));
        $this->assert->equals($homerConfirmed->getOwner(), new Binary('marge'));
        $this->assert->equals($homerConfirmed->getValue(), new Fraction(5));
    }

    function tree() {
        $a = $this->lib->issueCoin(new Binary('i key'), new Promise(new Binary('c'), 'p'), new Output(new Binary('a'), new Fraction(5)));
        $b = $this->lib->issueCoin(new Binary('i key'), new Promise(new Binary('c'), 'p'), new Output(new Binary('b'), new Fraction(7)));
        $c = $this->lib->issueCoin(new Binary('i key'), new Promise(new Binary('c'), 'p'), new Output(new Binary('c'), new Fraction(8)));

        $d = [
            $this->lib->transferCoins(new Binary('a key'), [$a], [
                new Output(new Binary('d'), new Fraction(4)),
                new Output(new Binary('x'), new Fraction(1))
            ])[0],
            $this->lib->transferCoins(new Binary('b key'), [$b], [
                new Output(new Binary('d'), new Fraction(2)),
                new Output(new Binary('x'), new Fraction(5))
            ])[0]
        ];

        $e = [
            $this->lib->transferCoins(new Binary('c key'), [$c], [
                new Output(new Binary('e'), new Fraction(7)),
                new Output(new Binary('x'), new Fraction(1))
            ])[0]
        ];

        $f = [
            $this->lib->transferCoins(new Binary('d key'), $d, [
                new Output(new Binary('f'), new Fraction(5)),
                new Output(new Binary('x'), new Fraction(1))
            ])[0],
            $this->lib->transferCoins(new Binary('e key'), $e, [
                new Output(new Binary('f'), new Fraction(4)),
                new Output(new Binary('x'), new Fraction(3)),
            ])[0]
        ];

        $g = $this->lib->transferCoins(new Binary('f key'), $f, [
            new Output(new Binary('g'), new Fraction(8)),
            new Output(new Binary('x'), new Fraction(1))
        ])[0];

        $confirmedA = $this->lib->confirmCoin(new Binary('a key'), $g);
        $confirmedB = $this->lib->confirmCoin(new Binary('b key'), $g);
        $confirmedC = $this->lib->confirmCoin(new Binary('c key'), $g);

        $this->assert->equals(
            $confirmedA->getValue()
            ->plus($confirmedB->getValue())
            ->plus($confirmedC->getValue()), $g->getValue());

        $this->assert->equals($confirmedA->getValue()->toFloat(), 2);
        $this->assert->equals($confirmedB->getValue()->toFloat(), 2.8);
        $this->assert->equals($confirmedC->getValue()->toFloat(), 3.2);
    }
}