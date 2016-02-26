<?php
namespace groupcash\php\io;

use groupcash\php\model\Authorization;
use groupcash\php\model\Signature;

class AuthorizationSerializer extends Serializer {

    const TOKEN = '__AUTH_JSON_A__';

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
            $this->inflateSignature($serialized['sig'])
        );
    }

    /**
     * @param Authorization $object
     * @return array
     */
    protected function serializeObject($object) {
        return [
            'issuer' => $object->getIssuerAddress(),
            'sig' => $this->serializeSignature($object->getSignature())
        ];
    }

    private function inflateSignature(array $array) {
        return new Signature($array['by'], $array['sign']);
    }

    private function serializeSignature(Signature $signature) {
        return [
            'by' => $signature->getSigner(),
            'sign' => $signature->getSign()
        ];
    }
}