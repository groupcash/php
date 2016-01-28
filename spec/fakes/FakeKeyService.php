<?php
namespace spec\groupcash\php\fakes;

use groupcash\php\KeyService;

class FakeKeyService implements KeyService {

    public $nextSign;

    /**
     * @return string
     */
    public function generatePrivateKey() {
        return 'my key';
    }

    /**
     * @param string $privateKey
     * @return string
     */
    public function publicKey($privateKey) {
        return "public $privateKey";
    }

    /**
     * @param string $content
     * @param string $privateKey
     * @return string
     */
    public function sign($content, $privateKey) {
        if ($this->nextSign) {
            $sign = $this->nextSign;
            $this->nextSign = null;
            return $sign;
        }
        $content = md5($content);
        return "$content signed with $privateKey";
    }

    /**
     * @param string $content
     * @param string $signed
     * @param string $publicKey
     * @return boolean
     */
    public function verify($content, $signed, $publicKey) {
        $key = str_replace("public ", "", $publicKey);
        return str_replace(" signed with $key", "", $signed) == md5($content);
    }
}