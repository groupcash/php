<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class CallbackTranscoder implements Transcoder {

    private $encode;
    private $hasEncoded;
    private $decode;

    /**
     * @param callable $encode
     * @param callable $decode
     */
    public function __construct(callable $encode, callable $decode) {
        $this->encode = $encode;
        $this->decode = $decode;

        $this->hasEncoded = function () {
            return true;
        };
    }

    /**
     * @param callable $hasEncoded
     * @return CallbackTranscoder
     */
    public function setHasEncoded(callable $hasEncoded) {
        $this->hasEncoded = $hasEncoded;
        return $this;
    }

    /**
     * @param mixed $input
     * @return string
     */
    public function encode($input) {
        return call_user_func($this->encode, $input);
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        return call_user_func($this->hasEncoded, $encoded);
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    public function decode($encoded) {
        return call_user_func($this->decode, $encoded);
    }

    /**
     * @return Transcoder
     */
    public function getBinaryTranscoder() {
        return new NoneTranscoder();
    }
}