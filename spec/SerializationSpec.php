<?php
namespace spec\groupcash\php;

use groupcash\php\cli\CoinSerializer;
use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Promise;
use groupcash\php\model\Signature;
use groupcash\php\model\Transference;
use rtens\scrut\Assert;

/**
 * The CLI application serializes coins for storing.
 *
 * @property Assert assert <-
 * @property CoinSerializer serializer <-
 */
class SerializationSpec {

    function simpleCoin() {
        $coin = new Coin(
            new Promise('myCurrency', 'public backer', 'a promise', 1),
            new Signature('issuer', 'my signature')
        );

        $array = [
            'ver' => '1.0',
            'coin' => [
                'trans' => [
                    'promise' => [
                        'currency' => 'myCurrency',
                        'descr' => 'a promise',
                        'serial' => 1,
                        'backer' => 'public backer']],
                'sig' => [
                    'signer' => 'issuer',
                    'signed' => 'my signature']]];

        $this->assert->equals($this->serializeToArray($coin), $array);
        $this->assert->equals($this->serializeUnserialize($coin), $coin);
    }

    function transferredCoin() {
        $coin = new Coin(
            new Transference(
                new Coin(
                    new Transference(
                        new Coin(
                            new Promise('myCurrency', 'public backer', 'a promise', 1),
                            new Signature('issuer', 'my signature')
                        ),
                        'first target',
                        new Fraction(1, 1),
                        'one'),
                    new Signature('first', 'first signature')
                ),
                'second target',
                new Fraction(1, 1),
                'two'),
            new Signature('second', 'second signature')
        );

        $array = [
            'ver' => '1.0',
            'coin' => [
                'trans' => [
                    'transfer' => [
                        'coin' => [
                            'trans' => [
                                'transfer' => [
                                    'coin' => [
                                        'trans' => [
                                            'promise' => [
                                                'currency' => 'myCurrency',
                                                'descr' => 'a promise',
                                                'serial' => 1,
                                                'backer' => 'public backer']],
                                        'sig' => [
                                            'signer' => 'issuer',
                                            'signed' => 'my signature']],
                                    'target' => 'first target',
                                    'fraction' => '1|1',
                                    'prev' => 'one']],
                            'sig' => [
                                'signer' => 'first',
                                'signed' => 'first signature']],
                        'target' => 'second target',
                        'fraction' => '1|1',
                        'prev' => 'two']],
                'sig' => [
                    'signer' => 'second',
                    'signed' => 'second signature']]];

        $this->assert->equals($this->serializeToArray($coin), $array);
        $this->assert->equals($this->serializeUnserialize($coin), $coin);
    }

    function fractionedTransference() {
        $first = new Coin(
            new Transference(
                new Coin(
                    new Promise('myCurrency', 'public backer', 'a promise', 1),
                    new Signature('issuer', 'my signature')
                ),
                'public first',
                new Fraction(3, 5), 'hash'),
            new Signature('backer', 'signature'));

        $array = [
            'ver' => '1.0',
            'coin' => [
                'trans' => [
                    'transfer' => [
                        'coin' => [
                            'trans' => [
                                'promise' => [
                                    'currency' => 'myCurrency',
                                    'descr' => 'a promise',
                                    'serial' => 1,
                                    'backer' => 'public backer']],
                            'sig' => [
                                'signer' => 'issuer',
                                'signed' => 'my signature']],
                        'target' => 'public first',
                        'fraction' => '3|5',
                        'prev' => 'hash']],
                'sig' => [
                    'signer' => 'backer',
                    'signed' => 'signature']]];

        $this->assert->equals($this->serializeToArray($first), $array);
        $this->assert->equals($this->serializeUnserialize($first), $first);
    }

    /**
     * @param $coin
     * @return mixed
     */
    private function serializeToArray($coin) {
        $json = json_decode($this->serializer->decode($this->serializer->serialize($coin)), true);
        return $json;
    }

    private function serializeUnserialize(Coin $coin) {
        return $this->serializer->unserialize($this->serializer->serialize($coin));
    }
}