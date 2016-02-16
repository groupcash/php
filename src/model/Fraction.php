<?php
namespace groupcash\php\model;

class Fraction {

    /** @var int */
    private $nominator;

    /** @var int */
    private $denominator;

    /**
     * @param int $nominator
     * @param int $denominator
     */
    public function __construct($nominator, $denominator = 1) {
        if ($nominator == 0) {
            $denominator = 1;
        } else if (function_exists('gmp_gcd')) {
            $gcd = gmp_intval(gmp_gcd((string)$nominator, (string)$denominator));
            $nominator /= $gcd;
            $denominator /= $gcd;
        }
        $this->denominator = $denominator;
        $this->nominator = $nominator;
    }

    /**
     * @return int
     */
    public function getDenominator() {
        return $this->denominator;
    }

    /**
     * @return int
     */
    public function getNominator() {
        return $this->nominator;
    }

    /**
     * @return float
     */
    public function toFloat() {
        return $this->nominator / $this->denominator;
    }

    function __toString() {
        return $this->nominator . ($this->denominator != 1 ? ('/' . $this->denominator) : '');
    }

    public function inverse() {
        if ($this->nominator == 0) {
            throw new \Exception('Cannot inverse zero.');
        }
        return new Fraction($this->denominator, $this->nominator);
    }

    public function negative() {
        return new Fraction(-$this->nominator, $this->denominator);
    }

    public function times(Fraction $fraction) {
        return new Fraction(
            $this->nominator * $fraction->nominator,
            $this->denominator * $fraction->denominator);
    }

    public function plus(Fraction $fraction) {
        return new Fraction(
            $this->nominator * $fraction->denominator + $fraction->nominator * $this->denominator,
            $this->denominator * $fraction->denominator
        );
    }

    public function minus(Fraction $fraction) {
        return $this->plus($fraction->negative());
    }

    public function dividedBy(Fraction $fraction) {
        return $this->times($fraction->inverse());
    }
}