<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class MsgPackTranscoder implements Transcoder {

    const MARKER = '@';

    /**
     * @return bool
     */
    public static function isAvailable() {
        return function_exists('msgpack_pack');
    }

    /**
     * @param mixed $input
     * @return string
     * @throws \Exception
     */
    public function encode($input) {
        if (function_exists('msgpack_pack')) {
            return self::MARKER . msgpack_pack($input);
        }
        throw new \Exception('msgpack not installed');
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        return substr($encoded, 0, 1) == self::MARKER;
    }

    /**
     * @param string $encoded
     * @return mixed
     * @throws \Exception
     */
    public function decode($encoded) {
        if (function_exists('msgpack_unpack')) {
            return msgpack_unpack(substr($encoded, 1));
        }
        throw new \Exception('msgpack not installed');
    }

    /**
     * @return Transcoder
     */
    public function getBinaryTranscoder() {
        return new NoneTranscoder();
    }
}