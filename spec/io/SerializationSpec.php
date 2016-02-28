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
        $this->serializer->registerTranscoder('foo', new CallbackTranscoder(
            'FOO',
            function ($array) {
                return '#' . json_encode($array) . '#';
            },
            function ($string) {
                return json_decode(substr($string, 1, -1));
            }
        ));
        $this->serializer->addTransformer(new CallbackTransformer(
            \DateTime::class,
            'BAR',
            function (\DateTime $dateTime) {
                return ['date' => $dateTime->format('c')];
            },
            function ($array) {
                return new \DateTime($array['date']);
            }
        ));
    }

    function defaultTranscoder() {
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 14:15:16 UTC'));
        $this->assert->equals($serialized, 'FOO#["BAR",{"date":"2011-12-13T14:15:16+00:00"}]#');
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
            $this->serializer->inflate('FOO#["WRONG",{}]#');
        });
        $this->try->thenTheException_ShouldBeThrown('New matching transformer available.');
    }

    function json() {
        $this->serializer->registerTranscoder('foo', new JsonTranscoder());
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 UTC'), 'foo');
        $this->assert->equals($serialized, 'JSON["BAR",{"date":"2011-12-13T00:00:00+00:00"}]');
    }

    function base64() {
        $this->serializer->registerTranscoder('foo', new Base64Transcoder(new JsonTranscoder()));
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 UTC'), 'foo');
        $this->assert->equals($serialized, 'SlNPTg==SlNPTlsiQkFSIix7ImRhdGUiOiIyMDExLTEyLTEzVDAwOjAwOjAwKzAwOjAwIn1d');
    }

    function messagePack() {
        if (!MsgPackTranscoder::isAvailable()) {
            $this->assert->incomplete('msgpack not installed');
        }

        $this->serializer->registerTranscoder('foo', new Base64Transcoder(new MsgPackTranscoder()));
        $serialized = $this->serializer->serialize(new \DateTime('2011-12-13 UTC'), 'foo');
        $this->assert->equals($serialized, 'TVNHUA==TVNHUJKjQkFSgaRkYXRluTIwMTEtMTItMTNUMDA6MDA6MDArMDA6MDA=');
    }
}