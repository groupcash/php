<?php
namespace groupcash\php\model;

class SplitCoin extends Coin {

    /** @var Fraction */
    private $fraction;

    public function __construct(Transaction $transaction, Signature $signature, Fraction $fraction) {
        parent::__construct($transaction, $signature);
        $this->fraction = $fraction;
    }

    public function transfer($newOwnerAddress, Signer $owner, $prev = null) {
        $transaction = new Transference($this, $newOwnerAddress, $prev);
        return new SplitCoin($transaction, $owner->sign($transaction), $this->fraction);
    }

    public function split(array $parts) {
        $sum = array_sum($parts);
        return array_map(function ($part) use ($sum) {
            $fraction = $this->fraction->times(new Fraction($part, $sum));
            return new SplitCoin($this->getTransaction(), $this->getSignature(), $fraction);
        }, $parts);
    }

    /**
     * @return Fraction
     */
    public function getFraction() {
        return $this->fraction;
    }
}