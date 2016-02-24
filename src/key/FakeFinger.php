<?php
namespace groupcash\php\key;

use groupcash\php\Finger;

class FakeFinger implements Finger {

    /**
     * @param mixed $content
     * @return string
     */
    public function makePrint($content) {
        return serialize($content);
    }
}