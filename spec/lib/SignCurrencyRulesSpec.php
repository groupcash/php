<?php
namespace spec\groupcash\php\lib;
use groupcash\php\algorithms\FakeAlgorithm;
use groupcash\php\Groupcash;
use groupcash\php\model\CurrencyRules;
use groupcash\php\model\signing\Binary;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * Each currency has a set of rules which can be updated.
 *
 * @property Groupcash lib
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class SignCurrencyRulesSpec {

    function before() {
        $this->lib = new Groupcash(new FakeAlgorithm());
    }

    function firstRules() {
        $rules = $this->lib->signCurrencyRules(new Binary('foo key'), 'My rules');

        $this->assert->equals($rules->getCurrencyAddress(), new Binary('foo'));
        $this->assert->equals($rules->getRules(), 'My rules');
        $this->assert->equals($rules->getPreviousHash(), null);
        $this->assert->equals($rules->getSignature(), 'foo' . "\0" . 'My rules' . "\0" . ' signed with foo key');
    }

    function wrongCurrency() {
        $previous = $this->lib->signCurrencyRules(new Binary('foo key'), 'My rules');

        $this->try->tryTo(function () use ($previous) {
            $this->lib->signCurrencyRules(new Binary('bar key'), 'New rules', $previous);
        });
        $this->try->thenTheException_ShouldBeThrown('Not signed by original currency');
    }

    function updateRules() {
        $previous = $this->lib->signCurrencyRules(new Binary('foo key'), 'My rules');
        $rules = $this->lib->signCurrencyRules(new Binary('foo key'), 'New rules', $previous);

        $this->assert->equals($rules->getPreviousHash(), $previous->hash());
        $this->assert->equals($rules->getSignature(),
            'foo' . "\0" . 'New rules' . "\0" . $previous->hash()->getData() . ' signed with foo key');
    }

    function validateSignature() {
        $this->try->tryTo(function () {
            $this->lib->verifyCurrencyRules([
                new CurrencyRules(
                    new Binary('foo'),
                    'Rules!',
                    null,
                    'foo' . "\0" . 'Rules!' . "\0" . ' signed with bar key'
                )
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid signature by [Zm9v]');
    }

    function missingPrevious() {
        $previous = $this->lib->signCurrencyRules(new Binary('foo key'), 'My rules');

        $this->try->tryTo(function () use ($previous) {
            $this->lib->verifyCurrencyRules([
                $this->lib->signCurrencyRules(new Binary('foo key'), 'My new rules', $previous)
            ]);
        });
        $this->try->thenTheException_ShouldBeThrown(
            'Previous rules not provided [gIU+qEdL2bdY8SAyiEp6a7MacHTbXXjW5RS1uijggHA=]');
    }
}