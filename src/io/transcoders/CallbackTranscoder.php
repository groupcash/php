<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class CallbackTranscoder extends Transcoder {

    /** @var callable */
    private $decode;

    /** @var callable */
    private $encode;

    /** @var string */
    private $token;

    /**
     * @param string $token
     * @param callable $encode
     * @param callable $decode
     */
    public function __construct($token, callable $encode, callable $decode) {
        $this->decode = $decode;
        $this->encode = $encode;
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function token() {
        return $this->token;
    }

    /**
     * @param mixed $input
     * @return string
     */
    protected function doEncode($input) {
        return call_user_func($this->encode, $input);
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    protected function doDecode($encoded) {
        return call_user_func($this->decode, $encoded);
    }
}