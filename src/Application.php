<?php
namespace groupcash\php;

class Application {

    /** @var KeyService */
    private $key;

    /** @var CryptoService */
    private $crypto;

    public function __construct(KeyService $key, CryptoService $crypto) {
        $this->key = $key;
        $this->crypto = $crypto;
    }

    public function generateKey($passPhrase = null) {
        $key = $this->key->generate();
        if ($passPhrase) {
            $key = $this->crypto->encrypt($key, $passPhrase);
        }
        return $key;
    }
}