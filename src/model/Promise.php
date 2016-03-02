<?php
namespace groupcash\php\model;
use groupcash\php\key\Binary;

/**
 * A Promise describes the delivery promise of a backer in a certain currency.
 *
 * The Output of the Promises Base defines how many units it is worth.
 */
class Promise implements Finger {

    /** @var Binary */
    private $currency;

    /** @var string */
    private $description;

    /**
     * @param Binary $currency
     * @param string $description
     */
    public function __construct(Binary $currency, $description) {
        $this->currency = $currency;
        $this->description = $description;
    }

    /**
     * @return Binary
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return [$this->currency, $this->description];
    }
}