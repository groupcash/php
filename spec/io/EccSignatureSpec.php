<?php
namespace spec\groupcash\php\io;

use groupcash\php\model\signing\Binary;
use groupcash\php\algorithms\EccAlgorithm;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 * @property EccAlgorithm $ecc
 */
class EccSignatureSpec {

    function before() {
        if (!getenv('WITH_ECC')) {
            $this->assert->incomplete('ECC spec skipped. To enable set WITH_ECC=1');
        }
        $this->ecc = new EccAlgorithm();
    }

    function publicKeyOfInvalidPrivateKey() {
        $this->try->tryTo(function () {
            $this->ecc->getAddress(new Binary('invalid'));
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function signWithInvalidPrivateKey() {
        $this->try->tryTo(function () {
            $this->ecc->sign('foo', new Binary('invalid'));
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function verifyWithInvalidSignature() {
        $this->try->tryTo(function () {
            $this->ecc->verify('foo', new Binary('invalid'), 'bar');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid signature.');
    }

    function verifyWithInvalidPublicKey() {
        $this->try->tryTo(function () {
            $this->ecc->verify('foo', new Binary('invalid'), 'foo#bar');
        });
        $this->try->thenTheException_ShouldBeThrown('Invalid key.');
    }

    function verifyWithWrongKey() {
        $key = $this->ecc->generateKey();
        $wrong = $this->ecc->generateKey();

        $signed = $this->ecc->sign('foo', $key);
        $this->assert->not($this->ecc->verify('foo', $this->ecc->getAddress($wrong), $signed));
    }

    function verifyWithWrongSignature() {
        $key = $this->ecc->generateKey();

        $signed = $this->ecc->sign('bar', $key);
        $this->assert->not($this->ecc->verify('foo', $this->ecc->getAddress($key), $signed));
    }

    function verifySignature() {
        $key = $this->ecc->generateKey();
        $signed = $this->ecc->sign('foo', $key);
        $this->assert->isTrue($this->ecc->verify('foo', $this->ecc->getAddress($key), $signed));
    }
}