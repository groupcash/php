<?php
namespace groupcash\php\key;

use groupcash\php\KeyService;
use groupcash\php\model\Signature;

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
     * @param Signature $signature
     * @return boolean
     */
    public function verify($content, Signature $signature) {
        return str_replace(" signed with {$signature->getSigner()} key", '', $signature->getSign()) == $content;
    }

    /**
     * @param string $content
     * @return string
     */
    public function hash($content) {
        return "#($content)";
    }
}