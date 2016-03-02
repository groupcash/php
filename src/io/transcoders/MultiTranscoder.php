<?php
namespace groupcash\php\io\transcoders;

use groupcash\php\io\Transcoder;

class MultiTranscoder implements Transcoder {

    /** @var Transcoder[] */
    private $transcoders;

    /**
     * @param Transcoder[] $transcoders
     */
    public function __construct(array $transcoders) {
        $this->transcoders = $transcoders;
    }

    /**
     * @param mixed $input
     * @return string
     */
    public function encode($input) {
        return $this->transcoders[0]->encode($input);
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        foreach ($this->transcoders as $transcoder) {
            if ($transcoder->hasEncoded($encoded)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $encoded
     * @return mixed
     * @throws \Exception
     */
    public function decode($encoded) {
        foreach ($this->transcoders as $transcoder) {
            if ($transcoder->hasEncoded($encoded)) {
                return $transcoder->decode($encoded);
            }
        }

        throw new \Exception('Unknown encoding');
    }

    /**
     * @return Transcoder
     */
    public function getBinaryTranscoder() {
        return new NoneTranscoder();
    }
}