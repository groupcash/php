<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\CoinSerializer;
use groupcash\php\io\transcoders\JsonTranscoder;
use groupcash\php\model\Base;
use groupcash\php\model\Coin;
use groupcash\php\model\Confirmation;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Transaction;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * A Coin can be serialized and de-serialized for transportation.
 *
 * @property CoinSerializer serializer
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class SerializationSpec {

    function before() {
        $this->serializer = new CoinSerializer([
            new JsonTranscoder()
        ]);
    }

    function wrongTranscoder() {
        $this->try->tryTo(function () {
            $this->serializer->inflate('foo');
        });
        $this->try->thenTheException_ShouldBeThrown('No matching transcoder registered');
    }

    function unsupported() {
        $this->try->tryTo(function () {
            $this->serializer->inflate(JsonTranscoder::TOKEN . json_encode(['foo']));
        });
        $this->try->thenTheException_ShouldBeThrown('Unsupported serialization.');
    }

    function unsupportedCoinVersion() {
        $coin = JsonTranscoder::TOKEN . json_encode([CoinSerializer::TOKEN, ['v' => 'foo']]);

        $this->try->tryTo(function () use ($coin) {
            $this->serializer->inflate($coin);
        });
        $this->try->thenTheException_ShouldBeThrown('Unsupported coin version.');
    }

    function complete() {
        $coin = new Coin(new Input(
            new Transaction(
                [new Input(
                    new Base(
                        new Promise('coin', 'My Promise'),
                        new Output('the backer', new Fraction(1)),
                        'the issuer', 'el issuero'
                    ),
                    0
                ), new Input(
                    new Confirmation(
                        [
                            new Base(
                                new Promise('foo', 'Her Promise'),
                                new Output('the backress', new Fraction(1)),
                                'the issuress', 'la issuera'
                            )
                        ],
                        new Output('apu', new Fraction(42)),
                        'my print',
                        'la lisa'),
                    0
                )],
                [
                    new Output('homer', new Fraction(3, 13)),
                    new Output('marge', new Fraction(0, 7)),
                ],
                'el barto'
            ),
            42
        ));

        $serialized = $this->serializer->serialize($coin);

        $this->assert->equals(substr($serialized, 0, 6), JsonTranscoder::TOKEN);
        $this->assert->equals($this->serializer->inflate($serialized), $coin);

        $decoded = json_decode(substr($serialized, 6), true);
        $this->assert->equals($decoded[0], CoinSerializer::TOKEN);
        $this->assert->equals($decoded[1], [
            'v' => $coin->version(),
            'in' => [
                'iout' => 42,
                'tx' => [
                    'ins' => [
                        [
                            'iout' => 0,
                            'tx' => [
                                'promise' => [
                                    'coin',
                                    'My Promise'
                                ],
                                'out' => [
                                    'to' => 'the backer',
                                    'val' => 1
                                ],
                                'by' => 'the issuer',
                                'sig' => 'el issuero'
                            ]
                        ],
                        [
                            'iout' => 0,
                            'tx' => [
                                'finger' => 'my print',
                                'bases' => [
                                    [
                                        'promise' => [
                                            'foo',
                                            'Her Promise'
                                        ],
                                        'out' => [
                                            'to' => 'the backress',
                                            'val' => 1
                                        ],
                                        'by' => 'the issuress',
                                        'sig' => 'la issuera'
                                    ]
                                ],
                                'out' => [
                                    'to' => 'apu',
                                    'val' => 42
                                ],
                                'sig' => 'la lisa'
                            ]
                        ]
                    ],
                    'outs' => [
                        [
                            'to' => 'homer',
                            'val' => '3|13'
                        ],
                        [
                            'to' => 'marge',
                            'val' => 0
                        ]
                    ],
                    'sig' => 'el barto'
                ]
            ]
        ]);
    }
}