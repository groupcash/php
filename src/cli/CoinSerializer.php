<?php
namespace groupcash\php\cli;

use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Promise;
use groupcash\php\model\Signature;
use groupcash\php\model\Transaction;
use groupcash\php\model\Transference;

class CoinSerializer {

    const VERSION = '1.0';

    /**
     * Presents a coin in a human-readable format.
     *
     * @param string $encoded
     * @param bool $pretty
     * @return string
     */
    public function decode($encoded, $pretty = false) {
        $decoded = base64_decode($encoded);
        if ($pretty) {
            $decoded = json_encode(json_decode($decoded, true), JSON_PRETTY_PRINT);
        }
        return $decoded;
    }

    /**
     * @param Coin $coin
     * @return string
     */
    public function serialize(Coin $coin) {
        $serialized = [
            'ver' => self::VERSION,
            'coin' => $this->arrayCoin($coin)
        ];
        return $this->encode(json_encode($serialized));
    }

    /**
     * @param string $serialized
     * @return Coin
     * @throws \Exception
     */
    public function unserialize($serialized) {
        $array = json_decode($this->decode($serialized), true);

        if ($array['ver'] != self::VERSION) {
            throw new \Exception('Unsupported serialization version');
        }

        return $this->objectCoin($array['coin']);
    }

    public function encode($serialized) {
        return base64_encode($serialized);
    }

    private function arrayCoin(Coin $coin) {
        return [
            'trans' => $this->arrayTransaction($coin->getTransaction()),
            'sig' => $this->arraySignature($coin->getSignature())
        ];
    }

    private function objectCoin(array $array) {
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
            'descr' => $promise->getDescription(),
            'serial' => $promise->getSerial(),
            'backer' => $promise->getBacker()
        ];
    }

    private function objectPromise(array $array) {
        return new Promise(
            $array['backer'],
            $array['descr'],
            $array['serial']
        );
    }

    private function arrayTransference(Transference $transference) {
        $fraction = $transference->getFraction();
        return [
            'coin' => $this->arrayCoin($transference->getCoin()),
            'target' => $transference->getTarget(),
            'fraction' => $fraction->getNominator() . '|' . $fraction->getDenominator(),
            'prev' => $transference->getPrev()
        ];
    }

    private function objectTransference(array $array) {
        list($nom, $den) = explode('|', $array['fraction']);
        return new Transference(
            $this->objectCoin($array['coin']),
            $array['target'],
            new Fraction($nom, $den),
            $array['prev']
        );
    }
}