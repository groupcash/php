<?php
namespace spec\groupcash\php\lib;

use groupcash\php\model\value\Fraction;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class FractionAlgebraSpec {

    function defaultDenominator() {
        $this->assert->equals(new Fraction(3), new Fraction(3, 1));
    }

    function zero() {
        $this->assert->equals(new Fraction(0, 1238), new Fraction(0, 1));
    }

    function reduce() {
        if (!function_exists('gmp_gcd')) {
            $this->assert->incomplete('gmp not installed');
        }

        $this->assert->equals(new Fraction(81, 12), new Fraction(27, 4));
    }

    function inverse() {
        $this->assert->equals((new Fraction(3, 4))->inverse(), new Fraction(4, 3));
    }

    function negate() {
        $this->assert->equals((new Fraction(3, 4))->negative(), new Fraction(-3, 4));
    }

    function inverseOfZero() {
        $this->try->tryTo(function () {
            (new Fraction(0))->inverse();
        });
        $this->try->thenTheException_ShouldBeThrown('Cannot inverse zero.');
    }

    function add() {
        $this->assert->equals((new Fraction(3, 4))->plus(new Fraction(3, 5)), new Fraction(27, 20));
    }

    function subtract() {
        $this->assert->equals((new Fraction(27, 20))->minus(new Fraction(3, 10)), new Fraction(210, 200));
    }

    function multiply() {
        $this->assert->equals((new Fraction(3, 4))->times(new Fraction(3, 5)), new Fraction(9, 20));
    }

    function divide() {
        $this->assert->equals((new Fraction(9, 20))->dividedBy(new Fraction(3, 5)), new Fraction(45, 60));
    }

    function greaterThan() {
        $this->assert->isTrue((new Fraction(8, 9))->isGreaterThan(new Fraction(7, 8)));
        $this->assert->not((new Fraction(8, 9))->isGreaterThan(new Fraction(8, 9)));
        $this->assert->not((new Fraction(7, 8))->isGreaterThan(new Fraction(8, 9)));
    }

    function lessThan() {
        $this->assert->not((new Fraction(8, 9))->isLessThan(new Fraction(7, 8)));
        $this->assert->not((new Fraction(8, 9))->isLessThan(new Fraction(8, 9)));
        $this->assert->isTrue((new Fraction(7, 8))->isLessThan(new Fraction(8, 9)));
    }

    function toFloat() {
        $this->assert->equals((new Fraction(1))->toFloat(), 1.0);
        $this->assert->equals((new Fraction(0))->toFloat(), 0.0);
        $this->assert->equals((new Fraction(3, 4))->toFloat(), 0.75);
        $this->assert->equals((new Fraction(5, 4))->toFloat(), 1.25);
        $this->assert->equals((new Fraction(1, 3))->toFloat(), 1/3);
    }
}