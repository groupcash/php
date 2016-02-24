<?php
namespace groupcash\php;

use groupcash\php\model\Signature;

class Signer {

    /** @var KeyService */
    private $service;

    /** @var Finger */
    private $finger;

    /** @var string */
    private $key;

    /**
     * @param KeyService $service
     * @param Finger $finger
     * @param string $key
     */
    public function __construct(KeyService $service, Finger $finger, $key) {
        $this->key = $key;
        $this->service = $service;
        $this->finger = $finger;
    }

    /**
     * @param mixed $content
     * @return Signature
     */
    public function sign($content) {
        return new Signature($this->getAddress(),
            $this->service->sign($this->finger->makePrint($content), $this->key));
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->service->publicKey($this->key);
    }
}