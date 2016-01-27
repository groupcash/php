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

    /**
     * @param string $key
     * @return string
     */
    public function publicKey($key) {
        return "public $key";
    }

    /**
     * @param string $content
     * @param string $key
     * @return string
     */
    public function sign($content, $key) {
        $content = md5($content);
        return "$content signed with $key";
    }

    /**
     * @param string $content
     * @param string $signature
     * @param string $publicKey
     * @return boolean
     */
    public function verify($content, $signature, $publicKey) {
        $key = str_replace("public ", "", $publicKey);
        return str_replace(" signed with $key", "", $signature) == $content;
    }
}