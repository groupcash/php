<?php
namespace groupcash\php\io;

use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Base;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Signature;
use groupcash\php\model\Transaction;

class CoinSerializer {

    const SERIALIZER_ID = '__JSON_A__';

    public function serialize(Coin $coin) {
        return self::SERIALIZER_ID . json_encode($this->serializeCoin($coin));
    }

    public function deserialize($serialized) {
        $serializerId = substr($serialized, 0, strlen(self::SERIALIZER_ID));
        if ($serializerId != self::SERIALIZER_ID) {
            throw new \Exception('Unsupported serialization.');
        }
        return $this->deserializeCoin(json_decode(substr($serialized, strlen(self::SERIALIZER_ID)), true));
    }

    private function serializeCoin(Coin $coin) {
        if ($coin->getVersion() != Coin::VERSION) {
            throw new \Exception('Unsupported coin version.');
        }
        return array_merge(
            [
                'v' => $coin->getVersion()
            ],
            $this->serializeInput($coin));
    }

    private function deserializeCoin($array) {
        if ($array['v'] != Coin::VERSION) {
            throw new \Exception('Unsupported coin version.');
        }

        $input = $this->deserializeInput($array);
        return new Coin(
            $input->getTransaction(),
            $input->getOutputIndex()
        );
    }

    private function serializeInput(Input $input) {
        return [
            'out#' => $input->getOutputIndex(),
            'tx' => $this->serializeTransaction($input->getTransaction())
        ];
    }

    private function deserializeInput($array) {
        return new Input(
            $this->deserializeTransaction($array['tx']),
            $array['out#']
        );
    }

    private function serializeTransaction(Transaction $transaction) {
        if ($transaction instanceof Base) {
            return $this->serializeIssue($transaction);
        }

        return [
            'in' => array_map([$this, 'serializeInput'], $transaction->getInputs()),
            'out' => array_map([$this, 'serializeOutput'], $transaction->getOutputs()),
            'sig' => $this->serializeSignature($transaction->getSignature())
        ];
    }

    private function deserializeTransaction($array) {
        if (array_key_exists('promise', $array)) {
            return $this->deserializeIssue($array);
        }

        return new Transaction(
            array_map([$this, 'deserializeInput'], $array['in']),
            array_map([$this, 'deserializeOutput'], $array['out']),
            $this->deserializeSignature($array['sig'])
        );
    }

    private function serializeIssue(Base $issue) {
        return [
            'promise'=> $this->serializePromise($issue->getPromise()),
            'out' => $this->serializeOutput($issue->getOutput()),
            'sig' => $this->serializeSignature($issue->getSignature())
        ];
    }

    private function deserializeIssue($array) {
        return new Base(
            $this->deserializePromise($array['promise']),
            $this->deserializeOutput($array['out']),
            $this->deserializeSignature($array['sig'])
        );
    }

    private function serializePromise(Promise $promise) {
        return [
            'currency' => $promise->getCurrency(),
            'descr' => $promise->getDescription()
        ];
    }

    private function deserializePromise($array) {
        return new Promise(
            $array['currency'],
            $array['descr']
        );
    }

    private function serializeOutput(Output $output) {
        return [
            'to' => $output->getTarget(),
            'val' => $this->serializeFraction($output->getValue())
        ];
    }

    private function deserializeOutput($array) {
        return new Output(
            $array['to'],
            $this->deserializeFraction($array['val'])
        );
    }

    private function serializeFraction(Fraction $fraction) {
        if ($fraction->getDenominator() == 1 || $fraction->getNominator() == 0) {
            return $fraction->getNominator();
        } else {
            return $fraction->getNominator() . '|' . $fraction->getDenominator();
        }
    }

    private function deserializeFraction($val) {
        if (strpos($val, '|')) {
            list($nom, $den) = explode('|', $val);
        } else {
            $nom = intval($val);
            $den = 1;
        }
        return new Fraction($nom, $den);
    }

    private function serializeSignature(Signature $signature) {
        return [
            'signer' => $signature->getSigner(),
            'sign' => $signature->getSign()
        ];
    }

    private function deserializeSignature($array) {
        return new Signature(
            $array['signer'],
            $array['sign']
        );
    }
}