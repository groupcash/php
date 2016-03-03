<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transcoder;
use groupcash\php\io\Transformer;
use groupcash\php\model\signing\Binary;
use groupcash\php\model\Authorization;

class AuthorizationTransformer implements Transformer {

    /**
     * @param string $class
     * @return bool
     */
    public function canTransform($class) {
        return $class == Authorization::class;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function hasTransformed($array) {
        return array_keys($array) == ['issuer', 'currency', 'sig'];
    }

    /**
     * @param array $array
     * @param Transcoder $transcoder
     * @return Authorization
     */
    public function toObject($array, Transcoder $transcoder) {
        return new Authorization(
            new Binary($transcoder->decode($array['issuer'])),
            new Binary($transcoder->decode($array['currency'])),
            $array['sig']
        );
    }

    /**
     * @param Authorization $object
     * @param Transcoder $transcoder
     * @return array
     */
    public function toArray($object, Transcoder $transcoder) {
        return [
            'issuer' => $transcoder->encode($object->getIssuerAddress()->getData()),
            'currency' => $transcoder->encode($object->getCurrencyAddress()->getData()),
            'sig' => $object->getSignature()
        ];
    }
}