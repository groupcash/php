<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class Base64Transcoder implements Transcoder {

    const MARKER = '&';

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
        return self::MARKER . base64_encode($this->inner->encode($input));
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        return substr($encoded, 0, 1) == self::MARKER && $this->inner->hasEncoded(base64_decode(substr($encoded, 1)));
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    public function decode($encoded) {
        return $this->inner->decode(base64_decode($encoded));
    }

    /**
     * @return Transcoder
     */
    public function getBinaryTranscoder() {
        return $this->inner->getBinaryTranscoder();
    }
}