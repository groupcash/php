<?php
namespace groupcash\php\model;

class Transference implements Transaction{

    /** @var Coin */
    private $coin;

    /** @var string */
    private $target;

    /** @var null|string */
    private $prev;

    /** @var Fraction */
    private $fraction;

    /**
     * @param Coin $coin
     * @param string $target
     * @param Fraction $fraction
     * @param null|string $prev
     */
    public function __construct(Coin $coin, $target, Fraction $fraction = null, $prev = null) {
        $this->coin = $coin;
        $this->target = $target;
        $this->fraction = $fraction ?: new Fraction(1);
        $this->prev = $prev;
    }

    /**
     * @return Coin
     */
    public function getCoin() {
        return $this->coin;
    }

    /**
     * @return string
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @return string
     */
    public function fingerprint() {
        $pieces = [$this->coin->getTransaction()->fingerprint(), $this->target];
        if ($this->prev) {
            $pieces[] = $this->prev;
        }
        return implode("", $pieces);
    }

    /**
     * @return null|string
     */
    public function getPrev() {
        return $this->prev;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->fraction . ' of ' . (string)$this->coin . ($this->prev ? " {{$this->prev}}" : '') . ' => ' . $this->target;
    }

    /**
     * @return Fraction
     */
    public function getFraction() {
        return $this->fraction;
    }
}