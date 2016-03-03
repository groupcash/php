<?php
namespace groupcash\php\model;

use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Finger;
use groupcash\php\model\value\Fraction;

/**
 * An Output specifies how much of a Transaction is transferred to a specific target.
 *
 * Each Output can only be referenced by a single Input.
 */
class Output implements Finger {

    /** @var Binary */
    private $target;

    /** @var Fraction */
    private $value;

    /**
     * @param Binary $target
     * @param Fraction $value
     */
    public function __construct(Binary $target, Fraction $value) {
        $this->target = $target;
        $this->value = $value;
    }

    /**
     * @return Binary
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @return Fraction
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return [$this->target, $this->value];
    }
}