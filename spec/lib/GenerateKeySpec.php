<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\signing\Binary;
use groupcash\php\algorithms\FakeAlgorithm;
use rtens\scrut\Assert;

/**
 * Keys are used by users to create cryptographic signatures. To each key belongs a unique address that
 * identifies the holder of the key.
 *
 * @property Assert assert <-
 * @property Groupcash lib
 */
class GenerateKeySpec {

    function before() {
        $this->lib = new Groupcash(new FakeAlgorithm());
    }

    function generate() {
        $key = $this->lib->generateKey();
        $this->assert->equals($key, new Binary('fake key'));
    }

    function getAddress() {
        $this->assert->equals($this->lib->getAddress(new Binary('my key')), new Binary('my'));
    }
}