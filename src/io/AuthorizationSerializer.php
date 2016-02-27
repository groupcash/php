<?php
namespace groupcash\php\io;

use groupcash\php\model\Authorization;

class AuthorizationSerializer extends Serializer {

    const TOKEN = '_AUTH_';

    /**
     * @return string Name of class that is serialized and inflated
     */
    public function serializes() {
        return Authorization::class;
    }

    /**
     * @return string
     */
    protected function token() {
        return self::TOKEN;
    }

    /**
     * @param array $serialized
     * @return Authorization
     */
    protected function inflateObject($serialized) {
        return new Authorization(
            $serialized['issuer'],
            $serialized['currency'],
            $serialized['sig']
        );
    }

    /**
     * @param Authorization $object
     * @return array
     */
    protected function serializeObject($object) {
        return [
            'issuer' => $object->getIssuerAddress(),
            'currency' => $object->getCurrencyAddress(),
            'sig' => $object->getSignature()
        ];
    }
}