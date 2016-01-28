<?php
namespace groupcash\php\model;

class Signature {

    /** @var string */
    private $signer;

    /** @var string */
    private $signed;

    /**
     * @param string $signer
     * @param string $signed
     */
    public function __construct($signer, $signed) {
        $this->signer = $signer;
        $this->signed = $signed;
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
    public function getSigned() {
        return $this->signed;
    }
}