<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transformer;
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
     * @return Authorization
     */
    public function toObject($array) {
        return new Authorization(
            $array['issuer'],
            $array['currency'],
            $array['sig']
        );
    }

    /**
     * @param Authorization $object
     * @return array
     */
    public function toArray($object) {
        return [
            'issuer' => $object->getIssuerAddress(),
            'currency' => $object->getCurrencyAddress(),
            'sig' => $object->getSignature()
        ];
    }
}