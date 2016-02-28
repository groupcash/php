<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transformer;
use groupcash\php\model\Authorization;

class AuthorizationTransformer extends Transformer {

    const TOKEN = 'AUTH';

    /**
     * @return string Name of class that is serialized and inflated
     */
    public function transforms() {
        return Authorization::class;
    }

    /**
     * @return string
     */
    protected function token() {
        return self::TOKEN;
    }

    /**
     * @param array $array
     * @return Authorization
     */
    protected function toObject($array) {
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
    protected function toArray($object) {
        return [
            'issuer' => $object->getIssuerAddress(),
            'currency' => $object->getCurrencyAddress(),
            'sig' => $object->getSignature()
        ];
    }
}