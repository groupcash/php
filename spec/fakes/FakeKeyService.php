<?php
namespace spec\groupcash\php\fakes;

use groupcash\php\KeyService;

class FakeKeyService implements KeyService {

    /** @var string */
    private $key;

    public function __construct($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function generate() {
        return $this->key;
    }
}