<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class HexadecimalTranscoder implements Transcoder {

    const MARKER = '0x';

    /** @var Transcoder */
    private $inner;

    public function __construct(Transcoder $inner) {
        $this->inner = $inner;
    }

    /**
     * @param mixed $input
     * @return string
     */
    public function encode($input) {
        return self::MARKER . bin2hex($this->inner->encode($input));
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        return substr($encoded, 0, 2) == self::MARKER;
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    public function decode($encoded) {
        return $this->inner->decode(hex2bin(substr($encoded, 2)));
    }
}