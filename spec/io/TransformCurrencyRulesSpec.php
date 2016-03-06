<?php
namespace spec\groupcash\php\io;
use groupcash\php\io\Transcoder;
use groupcash\php\io\transcoders\CallbackTranscoder;
use groupcash\php\io\transformers\CurrencyRulesTransformer;
use groupcash\php\model\CurrencyRules;
use groupcash\php\model\signing\Binary;
use rtens\scrut\Assert;

/**
 * @property CurrencyRulesTransformer transformer <-
 * @property Assert assert <-
 * @property Transcoder transcoder
 */
class TransformCurrencyRulesSpec {

    function before() {
        $this->transcoder = new CallbackTranscoder(function ($data) {
            return '#' . $data;
        }, function ($encoded) {
            return substr($encoded, 1);
        });
    }

    function onlyTransformsCurrencyRUles() {
        $this->assert->not($this->transformer->canTransform(\DateTime::class));
        $this->assert->isTrue($this->transformer->canTransform(CurrencyRules::class));
    }

    function roundTrip() {
        $rules = new CurrencyRules(
            new Binary('coin'),
            'My rules',
            new Binary('the previous'),
            'the signature'
        );

        $array = $this->transformer->toArray($rules, $this->transcoder);

        $this->assert->equals($array, [
            'by' => '#coin',
            'rules' => 'My rules',
            'prev' => '#the previous',
            'sig' => 'the signature'
        ]);
        $this->assert->isTrue($this->transformer->hasTransformed($array));
        $this->assert->equals($this->transformer->toObject($array, $this->transcoder), $rules);
    }

    function noPrevious() {
        $rules = new CurrencyRules(
            new Binary('coin'),
            'My rules',
            null,
            'the signature'
        );

        $array = $this->transformer->toArray($rules, $this->transcoder);

        $this->assert->equals($array, [
            'by' => '#coin',
            'rules' => 'My rules',
            'sig' => 'the signature'
        ]);
        $this->assert->isTrue($this->transformer->hasTransformed($array));
        $this->assert->equals($this->transformer->toObject($array, $this->transcoder), $rules);
    }
}