<?php
namespace spec\groupcash\php;

use groupcash\php\impl\EccKeyService;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 * @property EccKeyService service
 */
class EccKeySpec {

    function before() {
        if (getenv('SKIP_ECC')) {
            $this->assert->incomplete('ECC spec skipped.');
        }
        $this->service = new EccKeyService();
    }

    function publicKeyOfInvalidPrivateKey() {
        $this->try->tryTo(function () {
            $this->service->publicKey('invalid');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function signWithInvalidPrivateKey() {
        $this->try->tryTo(function () {
            $this->service->sign('foo', 'invalid');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function verifyWithInvalidSignature() {
        $this->try->tryTo(function () {
            $this->service->verify('foo', 'bar', 'invalid');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid signature.');
    }

    function verifyWithInvalidPublicKey() {
        $this->try->tryTo(function () {
            $this->service->verify('foo', 'foo#bar', 'invalid');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function verifyWithWrongKey() {
        $key = $this->service->generatePrivateKey();
        $wrong = $this->service->generatePrivateKey();

        $signed = $this->service->sign('foo', $key);
        $this->assert->not($this->service->verify('foo', $signed, $this->service->publicKey($wrong)));
    }

    function verifyWithWrongSignature() {
        $key = $this->service->generatePrivateKey();

        $signed = $this->service->sign('bar', $key);
        $this->assert->not($this->service->verify('foo', $signed, $this->service->publicKey($key)));
    }

    function verifySignature() {
        $key = $this->service->generatePrivateKey();
        $signed = $this->service->sign('foo', $key);
        $this->assert->isTrue($this->service->verify('foo', $signed, $this->service->publicKey($key)));
    }
}