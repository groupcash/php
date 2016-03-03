<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\transcoders\CallbackTranscoder;
use groupcash\php\io\transformers\AuthorizationTransformer;
use groupcash\php\model\signing\Binary;
use groupcash\php\model\Authorization;
use rtens\scrut\Assert;

/**
 * Like Coins, Authorizations are transformed
 *
 * @property AuthorizationTransformer transformer <-
 * @property Assert assert <-
 */
class TransformAuthorizationSpec {

    function onlyTransformsAuthorizations() {
        $this->assert->not($this->transformer->canTransform(\DateTime::class));
        $this->assert->isTrue($this->transformer->canTransform(Authorization::class));
    }

    function roundTrip() {
        $authorization = new Authorization(
            new Binary('issuer'),
            new Binary('currency'),
            'the signature'
        );

        $transcoder = new CallbackTranscoder(function ($data) {
            return '#' . $data;
        }, function ($encoded) {
            return substr($encoded, 1);
        });

        $array = $this->transformer->toArray($authorization, $transcoder);

        $this->assert->equals($array, [
            'issuer' => '#issuer',
            'currency' => '#currency',
            'sig' => 'the signature'
        ]);
        $this->assert->equals($this->transformer->toObject($array, $transcoder), $authorization);
    }
}