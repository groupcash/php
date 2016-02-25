<?php
namespace groupcash\php\model;

class Confirmation extends Transaction {

    /** @var string */
    private $fingerprint;

    /**
     * @param Input[] $inputs
     * @param Output[] $outputs
     * @param string $fingerprint
     * @param Signature $signature
     */
    public function __construct($inputs, $outputs, $fingerprint, Signature $signature) {
        parent::__construct($inputs, $outputs, $signature);
        $this->fingerprint = $fingerprint;
    }

    /**
     * @return string
     */
    public function getFingerprint() {
        return $this->fingerprint;
    }
}