<?php
namespace groupcash\php;

interface CryptoService {

    /**
     * @param string $text
     * @param string $key
     * @return string
     */
    public function encrypt($text, $key);

    /**
     * @param string $encrypted
     * @param string $key
     * @return string
     */
    public function decrypt($encrypted, $key);
}