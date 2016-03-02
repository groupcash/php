<?php
namespace groupcash\php\key;

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
     * @param string $publicKey
     * @param string $signature
     * @return bool
     */
    public function verify($content, $publicKey, $signature) {
        $signed = $this->sign($content, $publicKey . ' key');
        return $signed == $signature;
    }
}