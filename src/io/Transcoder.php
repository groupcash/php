<?php
namespace groupcash\php\io;

interface Transcoder {

    /**
     * @param mixed $input
     * @return string
     */
    public function encode($input);

    /**
     * @param string $encoded
     * @return bool
     */
    public function hasEncoded($encoded);

    /**
     * @param string $encoded
     * @return mixed
     */
    public function decode($encoded);
}