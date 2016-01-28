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

    public function sign(Transaction $transaction) {
        return new Signature($this->service->publicKey($this->key),
            $this->service->sign($transaction->fingerprint(), $this->key));
    }
}