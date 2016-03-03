<?php
namespace groupcash\php\model\signing;

interface Algorithm {

    /**
     * @return Binary
     */
    public function generateKey();

    /**
     * @param Binary $key
     * @return Binary
     */
    public function getAddress(Binary $key);

    /**
     * @param string $content
     * @param Binary $key
     * @return string
     */
    public function sign($content, Binary $key);

    /**
     * @param string $content
     * @param Binary $address
     * @param string $signature
     * @return bool
     */
    public function verify($content, Binary $address, $signature);
}