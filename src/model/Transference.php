<?php
namespace groupcash\php\model;

class Transference implements Transaction{

    /** @var Coin */
    private $coin;

    /** @var string */
    private $target;

    /** @var null|string */
    private $prev;

    /**
     * @param Coin $coin
     * @param string $target
     * @param null|string $prev
     */
    public function __construct(Coin $coin, $target, $prev = null) {
        $this->coin = $coin;
        $this->target = $target;
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
        return implode('--', [$this->coin->getTransaction()->fingerprint(), $this->target, $this->prev]);
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
        return (string)$this->coin . ($this->prev ? " {{$this->prev}}" : '') . ' => ' . $this->target;
    }
}