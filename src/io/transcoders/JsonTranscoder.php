<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class JsonTranscoder extends Transcoder {

    const TOKEN = '_JSON_';

    /**
     * @return string
     */
    public function token() {
        return self::TOKEN;
    }

    /**
     * @param mixed $input
     * @return string
     */
    protected function doEncode($input) {
        return json_encode($input);
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    protected function doDecode($encoded) {
        return json_decode($encoded, true);
    }
}