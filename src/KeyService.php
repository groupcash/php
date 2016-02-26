<?php
namespace groupcash\php;

use groupcash\php\model\Signature;

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
     * @param Signature $signature
     * @return boolean
     */
    public function verify($content, Signature $signature);

    /**
     * @param string $content
     * @return string
     */
    public function hash($content);
}