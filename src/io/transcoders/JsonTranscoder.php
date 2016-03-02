<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class JsonTranscoder implements Transcoder {

    /**
     * @param mixed $input
     * @return string
     */
    public function encode($input) {
        return json_encode($input);
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        return substr($encoded, 0, 1) == '{' && substr($encoded, -1) == '}';
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    public function decode($encoded) {
        return json_decode($encoded, true);
    }
}