<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * Keys are used by group members to create cryptographic signatures. To each key belongs a unique address that
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
        $this->assert->equals($key, 'my key');
    }

    function getAddress() {
        $this->assert->equals($this->lib->getAddress('key'), 'public key');
    }
}