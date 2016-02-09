<?php
namespace groupcash\php\model;

use groupcash\php\KeyService;

class Signer {

    /** @var KeyService */
    private $service;

    /** @var string */
    private $key;

    /**
     * @param KeyService $service
     * @param string $key
     */
    public function __construct(KeyService $service, $key) {
        $this->key = $key;
        $this->service = $service;
    }

    /**
     * @param string $content
     * @return Signature
     */
    public function sign($content) {
        return new Signature($this->getAddress(),
            $this->service->sign($content, $this->key));
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->service->publicKey($this->key);
    }
}