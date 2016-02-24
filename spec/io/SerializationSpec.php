<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\CoinSerializer;
use groupcash\php\model\Coin;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Issue;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Signature;
use groupcash\php\model\Transaction;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * A Coin can be serialized and de-serialized for transportation.
 *
 * @property CoinSerializer serializer <-
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class SerializationSpec {

    function unsupported() {
        $this->try->tryTo(function () {
            $this->serializer->deserialize('foo');
        });
        $this->try->thenTheException_ShouldBeThrown('Unsupported serialization.');
    }

    function unsupportedCoinVersionIn() {
        $coin = new Coin(
            'foo',
            new Transaction([], [], new Signature('', '')), 0
        );

        $this->try->tryTo(function () use ($coin) {
            $this->serializer->serialize($coin);
        });
        $this->try->thenTheException_ShouldBeThrown('Unsupported coin version.');
    }

    function unsupportedCoinVersionOut() {
        $coin = CoinSerializer::SERIALIZER_ID . '{"v":"foo"}';

        $this->try->tryTo(function () use ($coin) {
            $this->serializer->deserialize($coin);
        });
        $this->try->thenTheException_ShouldBeThrown('Unsupported coin version.');
    }

    function transferredCoin() {
        $coin = new Coin(
            '1.0',
            new Transaction(
                [new Input(
                    new Issue(
                        new Promise('coin', 'My Promise'),
                        new Output('the backer', new Fraction(1)),
                        new Signature('the issuer', 'el issuero')
                    ),
                    0
                )],
                [
                    new Output('homer', new Fraction(3, 13)),
                    new Output('marge', new Fraction(0, 7)),
                ],
                new Signature('bart', 'el barto')
            ),
            42
        );

        $serialized = $this->serializer->serialize($coin);

        $this->assert->equals(substr($serialized, 0, 10), CoinSerializer::SERIALIZER_ID);
        $this->assert->equals($this->serializer->deserialize($serialized), $coin);
        $this->assert->equals(json_decode(substr($serialized, 10), true), [
            'v' => '1.0',
            'out#' => 42,
            'tx' => [
                'in' => [
                    [
                        'out#' => 0,
                        'tx' => [
                            'promise' => [
                                'currency' => 'coin',
                                'descr' => 'My Promise'
                            ],
                            'out' => [
                                'to' => 'the backer',
                                'val' => 1
                            ],
                            'sig' => [
                                'signer' => 'the issuer',
                                'sign' => 'el issuero'
                            ]
                        ]
                    ]
                ],
                'out' => [
                    [
                        'to' => 'homer',
                        'val' => '3|13'
                    ],
                    [
                        'to' => 'marge',
                        'val' => 0
                    ]
                ],
                'sig' => [
                    'signer' => 'bart',
                    'sign' => 'el barto'
                ]
            ]
        ]);
    }
}