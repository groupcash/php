<?php
namespace spec\groupcash\php\fakes;

use groupcash\php\CryptoService;

class FakeCryptoService implements CryptoService {

    /**
     * @param string $text
     * @param string $key
     * @return string
     */
    public function encrypt($text, $key) {
        return "$text encrypted with $key";
    }

    /**
     * @param string $encrypted
     * @param string $key
     * @return string
     */
    public function decrypt($encrypted, $key) {
        return str_replace(" encrypted with $key", "", $encrypted);
    }
}