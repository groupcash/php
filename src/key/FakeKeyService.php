<?php
namespace groupcash\php\key;

use groupcash\php\KeyService;

class FakeKeyService implements KeyService {

    public $nextSign;

    public $nextKey;

    /**
     * @return string
     */
    public function generatePrivateKey() {
        $key = $this->nextKey ?: 'fake';
        $this->nextKey = null;
        return $key . ' key';
    }

    /**
     * @param string $privateKey
     * @return string
     */
    public function publicKey($privateKey) {
        return str_replace(' key', '', $privateKey);
    }

    /**
     * @param string $content
     * @param string $privateKey
     * @return string
     */
    public function sign($content, $privateKey) {
        $sign = $this->nextSign ?: "$content signed with $privateKey";
        $this->nextSign = null;
        return $sign;
    }

    /**
     * @param string $content
     * @param string $signed
     * @param string $publicKey
     * @return boolean
     */
    public function verify($content, $signed, $publicKey) {
        return str_replace(" signed with $publicKey key", '', $signed) == $content;
    }

    /**
     * @param string $content
     * @return string
     */
    public function hash($content) {
        return "($content)";
    }
}