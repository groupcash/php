<?php
namespace groupcash\php;

interface KeyService {

    /**
     * @return string
     */
    public function generate();

    /**
     * @param string $key
     * @return string
     */
    public function publicKey($key);

    /**
     * @param string $content
     * @param string $key
     * @return string
     */
    public function sign($content, $key);

    /**
     * @param string $content
     * @param string $signature
     * @param string $publicKey
     * @return boolean
     */
    public function verify($content, $signature, $publicKey);
}