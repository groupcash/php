<?php
namespace groupcash\php\io;

use groupcash\php\model\Coin;
use groupcash\php\model\Confirmation;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Base;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Transaction;

class CoinSerializer extends Serializer {

    const TOKEN = '_COIN_';

    private static $SUPPORTED_VERSIONS = ['dev'];

    /**
     * @return string Name of class that is serialized and inflated
     */
    public function serializes() {
        return Coin::class;
    }

    /**
     * @return string
     */
    protected function token() {
        return self::TOKEN;
    }

    /**
     * @param Coin $object
     * @return array
     */
    protected function serializeObject($object) {
        return $this->serializeCoin($object);
    }

    /**
     * @param array $serialized
     * @return object
     */
    protected function inflateObject($serialized) {
        return $this->inflateCoin($serialized);
    }

    private function serializeCoin(Coin $coin) {
        return [
            'v' => $coin->version(),
            'in' => $this->serializeInput($coin->getInput())
        ];
    }

    private function inflateCoin($array) {
        if (!in_array($array['v'], self::$SUPPORTED_VERSIONS)) {
            throw new \Exception('Unsupported coin version.');
        }

        return new Coin(
            $this->inflateInput($array['in'])
        );
    }

    private function serializeInput(Input $input) {
        return [
            'iout' => $input->getOutputIndex(),
            'tx' => $this->serializeTransaction($input->getTransaction())
        ];
    }

    private function inflateInput($array) {
        return new Input(
            $this->inflateTransaction($array['tx']),
            $array['iout']
        );
    }

    private function serializeTransaction(Transaction $transaction) {
        if ($transaction instanceof Base) {
            return $this->serializeBase($transaction);
        } else if ($transaction instanceof Confirmation) {
            return $this->serializeConfirmation($transaction);
        }

        return [
            'ins' => array_map([$this, 'serializeInput'], $transaction->getInputs()),
            'outs' => array_map([$this, 'serializeOutput'], $transaction->getOutputs()),
            'sig' => $transaction->getSignature()
        ];
    }

    private function inflateTransaction($array) {
        if (array_key_exists('promise', $array)) {
            return $this->inflateBase($array);
        } else if (array_key_exists('finger', $array)) {
            return $this->inflateConfirmation($array);
        }

        return new Transaction(
            array_map([$this, 'inflateInput'], $array['ins']),
            array_map([$this, 'inflateOutput'], $array['outs']),
            $array['sig']
        );
    }

    private function serializeBase(Base $base) {
        return [
            'promise' => $this->serializePromise($base->getPromise()),
            'out' => $this->serializeOutput($base->getOutput()),
            'by' => $base->getIssuerAddress(),
            'sig' => $base->getSignature()
        ];
    }

    private function inflateBase($array) {
        return new Base(
            $this->inflatePromise($array['promise']),
            $this->inflateOutput($array['out']),
            $array['by'],
            $array['sig']
        );
    }

    private function serializeConfirmation(Confirmation $confirmation) {
        return [
            'finger' => $confirmation->getHash(),
            'bases' => array_map([$this, 'serializeBase'], $confirmation->getBases()),
            'out' => $this->serializeOutput($confirmation->getOutput()),
            'sig' => $confirmation->getSignature()
        ];
    }

    private function inflateConfirmation($array) {
        return new Confirmation(
            array_map([$this, 'inflateBase'], $array['bases']),
            $this->inflateOutput($array['out']),
            $array['finger'],
            $array['sig']
        );
    }

    private function serializePromise(Promise $promise) {
        return [
            $promise->getCurrency(),
            $promise->getDescription()
        ];
    }

    private function inflatePromise($array) {
        return new Promise(
            $array[0],
            $array[1]
        );
    }

    private function serializeOutput(Output $output) {
        return [
            'to' => $output->getTarget(),
            'val' => $this->serializeFraction($output->getValue())
        ];
    }

    private function inflateOutput($array) {
        return new Output(
            $array['to'],
            $this->inflateFraction($array['val'])
        );
    }

    private function serializeFraction(Fraction $fraction) {
        if ($fraction->getDenominator() == 1 || $fraction->getNominator() == 0) {
            return $fraction->getNominator();
        } else {
            return $fraction->getNominator() . '|' . $fraction->getDenominator();
        }
    }

    private function inflateFraction($val) {
        if (strpos($val, '|')) {
            list($nom, $den) = explode('|', $val);
        } else {
            $nom = intval($val);
            $den = 1;
        }
        return new Fraction($nom, $den);
    }
}