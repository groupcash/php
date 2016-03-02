<?php
namespace groupcash\php\key;

class FakeKeyService implements KeyService {

    public $nextSign;

    public $nextKey;

    /**
     * @return Binary
     */
    public function generatePrivateKey() {
        $key = $this->nextKey ?: 'fake';
        $this->nextKey = null;
        return new Binary($key . ' key');
    }

    /**
     * @param Binary $privateKey
     * @return Binary
     */
    public function publicKey(Binary $privateKey) {
        return new Binary(str_replace(' key', '', $privateKey->getData()));
    }

    /**
     * @param string $content
     * @param Binary $privateKey
     * @return string
     */
    public function sign($content, Binary $privateKey) {
        $sign = $this->nextSign ?: "$content signed with {$privateKey->getData()}";
        $this->nextSign = null;
        return $sign;
    }

    /**
     * @param string $content
     * @param Binary $publicKey
     * @param string $signature
     * @return bool
     */
    public function verify($content, Binary $publicKey, $signature) {
        $signed = $this->sign($content, new Binary($publicKey->getData() . ' key'));
        return $signed == $signature;
    }
}