<?php
namespace groupcash\php\model\signing;

interface KeyService {

    /**
     * @return Binary
     */
    public function generatePrivateKey();

    /**
     * @param Binary $privateKey
     * @return Binary
     */
    public function publicKey(Binary $privateKey);

    /**
     * @param string $content
     * @param Binary $privateKey
     * @return string
     */
    public function sign($content, Binary $privateKey);

    /**
     * @param string $content
     * @param Binary $publicKey
     * @param string $signature
     * @return bool
     */
    public function verify($content, Binary $publicKey, $signature);
}