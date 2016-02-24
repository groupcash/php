<?php
namespace groupcash\php;

use groupcash\php\model\Fraction;
use groupcash\php\model\Issue;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;

class Groupcash {

    /** @var KeyService */
    private $key;

    /** @var Finger */
    private $finger;

    /**
     * @param KeyService $key
     * @param Finger $finger
     */
    public function __construct(KeyService $key, Finger $finger) {
        $this->key = $key;
        $this->finger = $finger;
    }

    public function issueCoin($issuerKey, Promise $promise, $backerAddress, Fraction $value) {
        return Issue::coin($promise, new Output($backerAddress, $value), new Signer($this->key, $this->finger, $issuerKey));
    }
}