<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeCryptoService;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * Keys are used by group members to create cryptographic signatures.
 *
 * @property Assert assert <-
 * @property Application app
 */
class GenerateKeySpec {

    function before() {
        $this->app = new Application(new FakeKeyService('my key'), new FakeCryptoService());
    }

    function withoutPassPhrase() {
        $key = $this->app->generateKey();
        $this->assert->equals($key, 'my key');
        $this->assert->equals($this->app->publicKey($key), 'public my key');
    }

    function withPassPhrase() {
        $key = $this->app->generateKey('secret');
        $this->assert->equals($key, 'my key encrypted with secret');
        $this->assert->equals($this->app->publicKey($key, 'secret'),'public my key');
    }
}