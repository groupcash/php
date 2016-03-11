<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\FractionParser;
use groupcash\php\model\value\Fraction;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * Converts a string to a Fraction
 *
 * @property FractionParser parser
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class ParseFractionSpec {

    function before() {
        $this->parser = new FractionParser();
    }

    function parseFraction() {
        $fraction = $this->parser->parse('4/5');
        $this->assert->equals($fraction, new Fraction(4, 5));
    }

    function badlyFormatted() {
        $this->try->tryTo(function () {
            $this->parser->parse('foo');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid fraction format.');
    }

    function parseInteger() {
        $fraction = $this->parser->parse('42');
        $this->assert->equals($fraction, new Fraction(42));
    }

    function parseFloat() {
        $fraction = $this->parser->parse('4.250');
        $this->assert->equals($fraction, new Fraction(425, 100));
    }

    function limit() {
        $this->try->tryTo(function () {
            $this->parser->parse('4.123456789');
        });
        $this->try->thenTheException_ShouldBeThrown('Maximum precision of 1/' . PHP_INT_MAX . ' exceeded.');
    }
}