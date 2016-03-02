<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class NoneTranscoder implements Transcoder {

    /**
     * @param mixed $input
     * @return string
     */
    public function encode($input) {
        return $input;
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        return true;
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    public function decode($encoded) {
        return $encoded;
    }

    /**
     * @return Transcoder
     */
    public function getBinaryTranscoder() {
        return new NoneTranscoder();
    }
}