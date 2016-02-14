<?php
namespace groupcash\php\cli;

use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Promise;
use groupcash\php\model\Signature;
use groupcash\php\model\Transaction;
use groupcash\php\model\Transference;

class CoinSerializer extends Serializer{

    public function serializes() {
        return Coin::class;
    }

    protected function version() {
        return '1.0';
    }

    public function objectKey() {
        return 'coin';
    }

    /**
     * @param Coin $coin
     * @return array
     * @throws \Exception
     */
    protected function serializeObject($coin) {
        return [
            'trans' => $this->arrayTransaction($coin->getTransaction()),
            'sig' => $this->arraySignature($coin->getSignature())
        ];
    }

    protected function inflateObject($array) {
        return new Coin(
            $this->objectTransaction($array['trans']),
            $this->objectSignature($array['sig'])
        );
    }

    private function arrayTransaction(Transaction $transaction) {
        if ($transaction instanceof Promise) {
            return [
                'promise' => $this->arrayPromise($transaction)
            ];
        } else if ($transaction instanceof Transference) {
            return [
                'transfer' => $this->arrayTransference($transaction)
            ];
        }

        throw new \Exception('Invalid coin.');
    }

    private function objectTransaction(array $array) {
        if (isset($array['promise'])) {
            return $this->objectPromise($array['promise']);
        } else if (isset($array['transfer'])) {
            return $this->objectTransference($array['transfer']);
        }

        throw new \Exception('Invalid coin.');
    }

    private function arraySignature(Signature $signature) {
        return [
            'signer' => $signature->getSigner(),
            'signed' => $signature->getSigned()
        ];
    }

    private function objectSignature(array $array) {
        return new Signature($array['signer'], $array['signed']);
    }

    private function arrayPromise(Promise $promise) {
        return [
            'currency' => $promise->getCurrency(),
            'descr' => $promise->getDescription(),
            'serial' => $promise->getSerial(),
            'backer' => $promise->getBacker()
        ];
    }

    private function objectPromise(array $array) {
        return new Promise(
            $array['currency'], $array['backer'], $array['descr'], $array['serial']
        );
    }

    private function arrayTransference(Transference $transference) {
        $fraction = $transference->getFraction();
        return [
            'coin' => $this->serializeObject($transference->getCoin()),
            'target' => $transference->getTarget(),
            'fraction' => $fraction->getNominator() . '|' . $fraction->getDenominator(),
            'prev' => $transference->getPrev()
        ];
    }

    private function objectTransference(array $array) {
        list($nom, $den) = explode('|', $array['fraction']);
        return new Transference(
            $this->inflateObject($array['coin']),
            $array['target'],
            new Fraction($nom, $den),
            $array['prev']
        );
    }
}