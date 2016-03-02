<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transcoder;
use groupcash\php\io\Transformer;
use groupcash\php\key\Binary;
use groupcash\php\model\Coin;
use groupcash\php\model\Confirmation;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Base;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Transaction;

class CoinTransformer implements Transformer {

    private static $SUPPORTED_VERSIONS = ['dev'];

    /**
     * @param string $class
     * @return bool
     */
    public function canTransform($class) {
        return $class == Coin::class;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function hasTransformed($array) {
        return array_keys($array) == ['v', 'coin'] && in_array($array['v'], self::$SUPPORTED_VERSIONS);
    }

    /**
     * @param Coin $object
     * @param Transcoder $transcoder
     * @return array
     */
    public function toArray($object, Transcoder $transcoder) {
        return $this->CoinToArray($object, $transcoder);
    }

    /**
     * @param array $array
     * @param Transcoder $transcoder
     * @return object
     */
    public function toObject($array, Transcoder $transcoder) {
        return $this->arrayToCoin($array['coin'], $transcoder);
    }

    private function CoinToArray(Coin $coin, Transcoder $transcoder) {
        return [
            'v' => $coin->version(),
            'coin' => $this->InputToArray($coin->getInput(), $transcoder)
        ];
    }

    private function arrayToCoin($array, Transcoder $transcoder) {
        return new Coin(
            $this->arrayToInput($array, $transcoder)
        );
    }

    private function InputToArray(Input $input, Transcoder $transcoder) {
        return [
            'iout' => $input->getOutputIndex(),
            'tx' => $this->TransactionToArray($input->getTransaction(), $transcoder)
        ];
    }

    private function arrayToInput($array, Transcoder $transcoder) {
        return new Input(
            $this->arrayToTransaction($array['tx'], $transcoder),
            $array['iout']
        );
    }

    private function TransactionToArray(Transaction $transaction, Transcoder $transcoder) {
        if ($transaction instanceof Base) {
            return $this->BaseToArray($transaction, $transcoder);
        } else if ($transaction instanceof Confirmation) {
            return $this->ConfirmationToArray($transaction, $transcoder);
        }

        return [
            'ins' => array_map(function (Input $input) use ($transcoder) {
                return $this->InputToArray($input, $transcoder);
            }, $transaction->getInputs()),
            'outs' => array_map(function (Output $output) use ($transcoder) {
                return $this->OutputToArray($output, $transcoder);
            }, $transaction->getOutputs()),
            'sig' => $transaction->getSignature()
        ];
    }

    private function arrayToTransaction($array, Transcoder $transcoder) {
        if (array_key_exists('promise', $array)) {
            return $this->arrayToBase($array, $transcoder);
        } else if (array_key_exists('finger', $array)) {
            return $this->arrayToConfirmation($array, $transcoder);
        }

        return new Transaction(
            array_map(function ($array) use ($transcoder) {
                return $this->arrayToInput($array, $transcoder);
            }, $array['ins']),
            array_map(function ($array) use ($transcoder) {
                return $this->arrayToOutput($array, $transcoder);
            }, $array['outs']),
            $array['sig']
        );
    }

    private function BaseToArray(Base $base, Transcoder $transcoder) {
        return [
            'promise' => $this->PromiseToArray($base->getPromise(), $transcoder),
            'out' => $this->OutputToArray($base->getOutput(), $transcoder),
            'by' => $transcoder->encode($base->getIssuerAddress()->getData()),
            'sig' => $base->getSignature()
        ];
    }

    private function arrayToBase($array, Transcoder $transcoder) {
        return new Base(
            $this->arrayToPromise($array['promise'], $transcoder),
            $this->arrayToOutput($array['out'], $transcoder),
            new Binary($transcoder->decode($array['by'])),
            $array['sig']
        );
    }

    private function ConfirmationToArray(Confirmation $confirmation, Transcoder $transcoder) {
        return [
            'finger' => $confirmation->getHash(),
            'bases' => array_map(function (Base $base) use ($transcoder) {
                return $this->BaseToArray($base, $transcoder);
            }, $confirmation->getBases()),
            'out' => $this->OutputToArray($confirmation->getOutput(), $transcoder),
            'sig' => $confirmation->getSignature()
        ];
    }

    private function arrayToConfirmation($array, Transcoder $transcoder) {
        return new Confirmation(
            array_map(function ($array) use ($transcoder) {
                return $this->arrayToBase($array, $transcoder);
            }, $array['bases']),
            $this->arrayToOutput($array['out'], $transcoder),
            $array['finger'],
            $array['sig']
        );
    }

    private function PromiseToArray(Promise $promise, Transcoder $transcoder) {
        return [
            $transcoder->encode($promise->getCurrency()->getData()),
            $promise->getDescription()
        ];
    }

    private function arrayToPromise($array, Transcoder $transcoder) {
        return new Promise(
            new Binary($transcoder->decode($array[0])),
            $array[1]
        );
    }

    private function OutputToArray(Output $output, Transcoder $transcoder) {
        return [
            'to' => $transcoder->encode($output->getTarget()->getData()),
            'val' => $this->FractionToArray($output->getValue())
        ];
    }

    private function arrayToOutput($array, Transcoder $transcoder) {
        return new Output(
            new Binary($transcoder->decode($array['to'])),
            $this->arrayToFraction($array['val'])
        );
    }

    private function FractionToArray(Fraction $fraction) {
        if ($fraction->getDenominator() == 1 || $fraction->getNominator() == 0) {
            return $fraction->getNominator();
        } else {
            return [$fraction->getNominator(), $fraction->getDenominator()];
        }
    }

    private function arrayToFraction($val) {
        if (is_array($val)) {
            list($nom, $den) = $val;
        } else {
            $nom = $val;
            $den = 1;
        }

        return new Fraction($nom, $den);
    }
}