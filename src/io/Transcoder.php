<?php
namespace groupcash\php\io;

abstract class Transcoder {

    /**
     * @return string
     */
    public abstract function token();

    /**
     * @param mixed $input
     * @return string
     */
    protected abstract function doEncode($input);

    /**
     * @param string $encoded
     * @return mixed
     */
    protected abstract function doDecode($encoded);

    /**
     * @param mixed $input
     * @return string
     */
    public function encode($input) {
        return $this->token() . $this->doEncode($input);
    }

    /**
     * @param string $encoded
     * @return mixed
     */
    public function decode($encoded) {
        return $this->doDecode(substr($encoded, strlen($this->token())));
    }

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded) {
        return substr($encoded, 0, strlen($this->token())) == $this->token();
    }
}