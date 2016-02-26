<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\key\FakeKeyService;
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
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function generate() {
        $key = $this->lib->generateKey();
        $this->assert->equals($key, 'fake key');
    }

    function getAddress() {
        $this->assert->equals($this->lib->getAddress('my key'), 'my');
    }
}