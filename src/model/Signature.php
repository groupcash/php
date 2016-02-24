<?php
namespace groupcash\php\model;

/**
 * A Signature contains the public key of the signer and the sign - the result of signing the
 * content with the signers private key.
 */
class Signature {

    /** @var string */
    private $signer;

    /** @var string */
    private $sign;

    /**
     * @param string $signer
     * @param string $sign
     */
    public function __construct($signer, $sign) {
        $this->signer = $signer;
        $this->sign = $sign;
    }

    /**
     * @return string
     */
    public function getSigner() {
        return $this->signer;
    }

    /**
     * @return string
     */
    public function getSign() {
        return $this->sign;
    }
}