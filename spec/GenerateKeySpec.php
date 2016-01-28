<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * Keys are used by group members to create cryptographic signatures. To each key belongs a unique address that
 * identifies the holder of the key.
 *
 * @property Assert assert <-
 * @property Application app
 */
class GenerateKeySpec {

    function before() {
        $this->app = new Application(new FakeKeyService());
    }

    function generate() {
        $key = $this->app->generateKey();
        $this->assert->equals($key, 'my key');
    }

    function getAddress() {
        $this->assert->equals($this->app->getAddress('key'), 'public key');
    }
}