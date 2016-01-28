<?php
namespace groupcash\php;

interface KeyService {

    /**
     * @return string
     */
    public function generatePrivateKey();

    /**
     * @param string $privateKey
     * @return string
     */
    public function publicKey($privateKey);

    /**
     * @param string $content
     * @param string $privateKey
     * @return string
     */
    public function sign($content, $privateKey);

    /**
     * @param string $content
     * @param string $signed
     * @param string $publicKey
     * @return boolean
     */
    public function verify($content, $signed, $publicKey);
}