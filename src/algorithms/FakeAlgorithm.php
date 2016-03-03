<?php
namespace groupcash\php\algorithms;

use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Algorithm;

class FakeAlgorithm implements Algorithm {

    public $nextSign;

    public $nextKey;

    /**
     * @return Binary
     */
    public function generateKey() {
        $key = $this->nextKey ?: 'fake';
        $this->nextKey = null;
        return new Binary($key . ' key');
    }

    /**
     * @param Binary $key
     * @return Binary
     */
    public function getAddress(Binary $key) {
        return new Binary(str_replace(' key', '', $key->getData()));
    }

    /**
     * @param string $content
     * @param Binary $key
     * @return string
     */
    public function sign($content, Binary $key) {
        $sign = $this->nextSign ?: "$content signed with {$key->getData()}";
        $this->nextSign = null;
        return $sign;
    }

    /**
     * @param string $content
     * @param Binary $address
     * @param string $signature
     * @return bool
     */
    public function verify($content, Binary $address, $signature) {
        $signed = $this->sign($content, new Binary($address->getData() . ' key'));
        return $signed == $signature;
    }
}