<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\Serializer;
use groupcash\php\io\transcoders\Base64Transcoder;
use groupcash\php\io\transcoders\CallbackTranscoder;
use groupcash\php\io\transcoders\JsonTranscoder;
use groupcash\php\io\transcoders\MsgPackTranscoder;
use groupcash\php\io\transformers\CallbackTransformer;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;
use groupcash\php\io\FractionParser;
use groupcash\php\model\Fraction;

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
}