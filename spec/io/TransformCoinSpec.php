<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\transcoders\CallbackTranscoder;
use groupcash\php\io\transformers\CoinTransformer;
use groupcash\php\model\signing\Binary;
use groupcash\php\model\Base;
use groupcash\php\model\Coin;
use groupcash\php\model\Confirmation;
use groupcash\php\model\value\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Output;
use groupcash\php\model\Transaction;
use rtens\scrut\Assert;

/**
 * For interoperability, coins are transformed to a standardized structure
 *
 * @property CoinTransformer transformer <-
 * @property Assert assert <-
 */
class TransformCoinSpec {

    function unsupportedCoinVersion() {
        $this->assert->not($this->transformer->hasTransformed(['v' => 'foo']));
    }

    function onlyTransformsCoin() {
        $this->assert->not($this->transformer->canTransform(\DateTime::class));
        $this->assert->isTrue($this->transformer->canTransform(Coin::class));
    }

    function roundTrip() {
        $coin = new Coin(new Input(
            new Transaction(
                [new Input(
                    new Base(
                        new Binary('coin'), 'My Promise',
                        new Output(new Binary('the backer'), new Fraction(1)),
                        new Binary('the issuer'), 'el issuero'
                    ),
                    0
                ), new Input(
                    new Confirmation(
                        [
                            new Base(
                                new Binary('foo'), 'Her Promise',
                                new Output(new Binary('the backress'), new Fraction(1)),
                                new Binary('the issuress'), 'la issuera'
                            )
                        ],
                        new Output(new Binary('apu'), new Fraction(42)),
                        'my print',
                        'la lisa'),
                    0
                )],
                [
                    new Output(new Binary('homer'), new Fraction(3, 13)),
                    new Output(new Binary('marge'), new Fraction(0, 7)),
                ],
                'el barto'
            ),
            42
        ));

        $transcoder = new CallbackTranscoder(function ($data) {
            return '#' . $data;
        }, function ($encoded) {
            return substr($encoded, 1);
        });

        $array = $this->transformer->toArray($coin, $transcoder);

        $this->assert->isTrue($this->transformer->hasTransformed($array));
        $this->assert->equals($array, [
            'v' => $coin->version(),
            'coin' => [
                'iout' => 42,
                'tx' => [
                    'ins' => [
                        [
                            'iout' => 0,
                            'tx' => [
                                'in' => '#coin',
                                'that' => 'My Promise',
                                'out' => [
                                    'to' => '#the backer',
                                    'val' => 1
                                ],
                                'by' => '#the issuer',
                                'sig' => 'el issuero'
                            ]
                        ],
                        [
                            'iout' => 0,
                            'tx' => [
                                'finger' => 'my print',
                                'bases' => [
                                    [
                                        'in' => '#foo',
                                        'that' => 'Her Promise',
                                        'out' => [
                                            'to' => '#the backress',
                                            'val' => 1
                                        ],
                                        'by' => '#the issuress',
                                        'sig' => 'la issuera'
                                    ]
                                ],
                                'out' => [
                                    'to' => '#apu',
                                    'val' => 42
                                ],
                                'sig' => 'la lisa'
                            ]
                        ]
                    ],
                    'outs' => [
                        [
                            'to' => '#homer',
                            'val' => [3, 13]
                        ],
                        [
                            'to' => '#marge',
                            'val' => 0
                        ]
                    ],
                    'sig' => 'el barto'
                ]
            ]
        ]);
        $this->assert->equals($this->transformer->toObject($array, $transcoder), $coin);
    }
}