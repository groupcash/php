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
     * @param Transaction $transaction
     * @return Signature
     */
    public function sign(Transaction $transaction) {
        return new Signature($this->getAddress(),
            $this->service->sign($transaction->fingerprint(), $this->key));
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->service->publicKey($this->key);
    }
}