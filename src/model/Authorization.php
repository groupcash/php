<?php
namespace groupcash\php\model;

use groupcash\php\KeyService;

class Authorization {

    /** @var string */
    private $issuer;

    /** @var Signature */
    private $signature;

    /**
     * @param string $issuer
     * @param Signature $signature
     */
    public function __construct($issuer, Signature $signature) {
        $this->issuer = $issuer;
        $this->signature = $signature;
    }

    public static function create($issuer, Signer $currency) {
        return new Authorization($issuer, $currency->sign($issuer));
    }

    public function isAuthorizedToIssue(Promise $promise, Signature $issue, KeyService $key) {
        return $issue->getSigner() == $this->issuer
            && $promise->getCurrency() == $this->signature->getSigner()
            && $key->verify($this->fingerprint(), $this->signature->getSigned(), $this->signature->getSigner());
    }

    private function fingerprint() {
        return $this->issuer;
    }
}