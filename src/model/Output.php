<?php
namespace groupcash\php\model;

/**
 * An Output specifies how much of a Transaction is transferred to a specific target.
 *
 * Each Output can only be referenced by a single Input.
 */
class Output implements Finger {

    /** @var string */
    private $target;

    /** @var Fraction */
    private $value;

    /**
     * @param string $target
     * @param Fraction $value
     */
    public function __construct($target, Fraction $value) {
        $this->target = $target;
        $this->value = $value;
    }

    /**
     * @return string
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