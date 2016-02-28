<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class MsgPackTranscoder extends Transcoder {

    /**
     * @return bool
     */
    public static function isAvailable() {
        return function_exists('msgpack_pack');
    }

    /**
     * @return string
     */
    public function token() {
        return 'MSGP';
    }

    /**
     * @param mixed $input
     * @return string
     * @throws \Exception
     */
    protected function doEncode($input) {
        if (function_exists('msgpack_pack')) {
            return msgpack_pack($input);
        }
        throw new \Exception('msgpack not installed');
    }

    /**
     * @param string $encoded
     * @return mixed
     * @throws \Exception
     */
    protected function doDecode($encoded) {
        if (function_exists('msgpack_unpack')) {
            return msgpack_unpack($encoded);
        }
        throw new \Exception('msgpack not installed');
    }
}