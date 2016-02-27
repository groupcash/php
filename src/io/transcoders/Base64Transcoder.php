<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class Base64Transcoder extends Transcoder {

    /** @var Transcoder */
    private $inner;

    public function __construct(Transcoder $inner) {
        $this->inner = $inner;
    }

    /**
     * @return string
     */
    public function token() {
        return base64_encode($this->inner->token());
    }

    /**
     * @param mixed $input
     * @return string
     */
    protected function doEncode($input) {
        return base64_encode($this->inner->encode($input));
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    protected function doDecode($encoded) {
        return $this->inner->decode(base64_decode($encoded));
    }
}