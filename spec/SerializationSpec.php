<?php
namespace spec\groupcash\php;

use groupcash\php\cli\CoinSerializer;
use groupcash\php\model\Coin;
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
            new Promise('public backer', 'a promise', 1),
            new Signature('issuer', 'my signature')
        );

        $array = [
            'ver' => '1.0',
            'coin' => [
                'trans' => [
                    'promise' => [
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
                            new Promise('public backer', 'a promise', 1),
                            new Signature('issuer', 'my signature')
                        ),
                        'first target',
                        'one'),
                    new Signature('first', 'first signature')
                ),
                'second target',
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
                                                'descr' => 'a promise',
                                                'serial' => 1,
                                                'backer' => 'public backer']],
                                        'sig' => [
                                            'signer' => 'issuer',
                                            'signed' => 'my signature']],
                                    'target' => 'first target',
                                    'prev' => 'one']],
                            'sig' => [
                                'signer' => 'first',
                                'signed' => 'first signature']],
                        'target' => 'second target',
                        'prev' => 'two']],
                'sig' => [
                    'signer' => 'second',
                    'signed' => 'second signature']]];

        $this->assert->equals($this->serializeToArray($coin), $array);
        $this->assert->equals($this->serializeUnserialize($coin), $coin);
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