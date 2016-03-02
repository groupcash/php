<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class JsonTranscoder implements Transcoder {

    /** @var Transcoder */
    private $binaryTranscoder;

    /**
     * @param null|Transcoder $binaryTranscoder
     */
    public function __construct(Transcoder $binaryTranscoder = null) {
        $this->binaryTranscoder = $binaryTranscoder ?: new MultiTranscoder([
            new Base64Transcoder(new NoneTranscoder()),
            new HexadecimalTranscoder(new NoneTranscoder())
        ]);
    }

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

    /**
     * @return Transcoder
     */
    public function getBinaryTranscoder() {
        return $this->binaryTranscoder;
    }
}