<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\Serializer;
use groupcash\php\io\transcoders\Base64Transcoder;
use groupcash\php\io\transcoders\CallbackTranscoder;
use groupcash\php\io\transcoders\HexadecimalTranscoder;
use groupcash\php\io\transcoders\JsonTranscoder;
use groupcash\php\io\transcoders\MsgPackTranscoder;
use groupcash\php\io\transformers\CallbackTransformer;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * A Coin can be serialized and de-serialized for transportation.
 *
 * @property Serializer serializer
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class SerializationSpec {

    function before() {
        $this->serializer = new Serializer();
        $this->serializer->registerTranscoder('foo',
            (new CallbackTranscoder(
                function ($array) {
                    return '#' . json_encode($array) . '#';
                },
                function ($string) {
                    return json_decode(substr($string, 1, -1), true);
                }
            ))->setHasEncoded(function ($str) {
                return substr($str, 0, 1) == '#';
            }));
        $this->serializer->addTransformer(
            (new CallbackTransformer(
                function (\DateTime $dateTime) {
                    return ['date' => $dateTime->format('c')];
                },
                function ($array) {
                    return new \DateTime($array['date']);
                }
            ))->setCanTransform(function ($class) {
                return $class == \DateTime::class;
            })->setHasTransformed(function ($array) {
                return isset($array['date']);
            }));
    }

    function handles() {
        $this->assert->isTrue($this->serializer->handles(new \DateTime()));
        $this->assert->isTrue($this->serializer->handles(\DateTime::class));
        $this->assert->not($this->serializer->handles(new \DateTimeImmutable()));
        $this->assert->not($this->serializer->handles(\DateTimeImmutable::class));
    }

    function wrongTranscoder() {
        $this->try->tryTo(function () {
            $this->serializer->serialize(new \DateTime(), 'wrong');
        });
        $this->try->thenTheException_ShouldBeThrown('Transcoder not registered: [wrong]');
    }

    function wrongToken() {
        $this->try->tryTo(function () {
            $this->serializer->inflate('WRONG');
        });
        $this->try->thenTheException_ShouldBeThrown('No matching transcoder registered.');
    }

    function unsupported() {
        $this->try->tryTo(function () {
            $this->serializer->inflate('#{}#');
        });
        $this->try->thenTheException_ShouldBeThrown('New matching transformer available.');
    }

    function json() {
        $this->serializer->registerTranscoder('foo', new JsonTranscoder());
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 UTC'), 'foo');
        $this->assert->equals($serialized, '{"date":"2011-12-13T00:00:00+00:00"}');
        $this->assert->equals($this->serializer->inflate($serialized), new \DateTime('2011-12-13 UTC'));
    }

    function base64() {
        $this->serializer->registerTranscoder('foo', new Base64Transcoder(new JsonTranscoder()));
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 UTC'), 'foo');
        $this->assert->equals($serialized, '!eyJkYXRlIjoiMjAxMS0xMi0xM1QwMDowMDowMCswMDowMCJ9');
        $this->assert->equals($this->serializer->inflate($serialized), new \DateTime('2011-12-13 UTC'));
    }

    function hexadecimal() {
        $this->serializer->registerTranscoder('foo', new HexadecimalTranscoder(new JsonTranscoder()));
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 UTC'), 'foo');
        $this->assert->equals($serialized, '0x7b2264617465223a22323031312d31322d31335430303a30303a30302b30303a3030227d');
        $this->assert->equals($this->serializer->inflate($serialized), new \DateTime('2011-12-13 UTC'));
    }

    function messagePack() {
        if (!MsgPackTranscoder::isAvailable()) {
            $this->assert->incomplete('msgpack not installed');
        }

        $this->serializer->registerTranscoder('foo', new MsgPackTranscoder());
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 UTC'), 'foo');
        $this->assert->equals($serialized, MsgPackTranscoder::MARKER . hex2bin('81a4') . 'date' . hex2bin('b9') . '2011-12-13T00:00:00+00:00');
        $this->assert->equals($this->serializer->inflate($serialized), new \DateTime('2011-12-13 UTC'));
    }
}