<?php
namespace spec\groupcash\php\io;

use groupcash\php\key\EccKeyService;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 * @property EccKeyService $ecc
 */
class EccSignatureSpec {

    function before() {
        if (getenv('SKIP_ECC')) {
            $this->assert->incomplete('ECC spec skipped.');
        }
        $this->ecc = new EccKeyService();
    }

    function publicKeyOfInvalidPrivateKey() {
        $this->try->tryTo(function () {
            $this->ecc->publicKey('invalid');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function signWithInvalidPrivateKey() {
        $this->try->tryTo(function () {
            $this->ecc->sign('foo', 'invalid');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function verifyWithInvalidSignature() {
        $this->try->tryTo(function () {
            $this->ecc->verify('foo', 'invalid', 'bar');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid signature.');
    }

    function verifyWithInvalidPublicKey() {
        $this->try->tryTo(function () {
            $this->ecc->verify('foo', 'invalid', 'foo#bar');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function verifyWithWrongKey() {
        $key = $this->ecc->generatePrivateKey();
        $wrong = $this->ecc->generatePrivateKey();

        $signed = $this->ecc->sign('foo', $key);
        $this->assert->not($this->ecc->verify('foo', $this->ecc->publicKey($wrong), $signed));
    }

    function verifyWithWrongSignature() {
        $key = $this->ecc->generatePrivateKey();

        $signed = $this->ecc->sign('bar', $key);
        $this->assert->not($this->ecc->verify('foo', $this->ecc->publicKey($key), $signed));
    }

    function verifySignature() {
        $key = $this->ecc->generatePrivateKey();
        $signed = $this->ecc->sign('foo', $key);
        $this->assert->isTrue($this->ecc->verify('foo', $this->ecc->publicKey($key), $signed));
    }
}