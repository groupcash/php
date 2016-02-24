<?php
namespace groupcash\php;

interface Finger {

    /**
     * @param mixed $content
     * @return string
     */
    public function makePrint($content);
}