<?php
namespace groupcash\php\cli;

use groupcash\php\model\Authorization;
use groupcash\php\model\Signature;

class AuthorizationSerializer extends Serializer {

    /**
     * @return string Name of class that is serialized and inflated
     */
    public function serializes() {
        return Authorization::class;
    }

    /**
     * @return string
     */
    public function objectKey() {
        return 'auth';
    }

    /**
     * @return string
     */
    protected function version() {
        return '1.0';
    }

    /**
     * @param array $serialized
     * @return Authorization
     */
    protected function inflateObject($serialized) {
        return new Authorization(
            $serialized['issuer'],
            $this->objectSignature($serialized['sig'])
        );
    }

    /**
     * @param Authorization $object
     * @return array
     */
    protected function serializeObject($object) {
        return [
            'issuer' => $object->getIssuer(),
            'sig' => $this->arraySignature($object->getSignature())
        ];
    }

    private function objectSignature(array $array) {
        return new Signature($array['signer'], $array['signed']);
    }

    private function arraySignature(Signature $signature) {
        return [
            'signer' => $signature->getSigner(),
            'signed' => $signature->getSigned()
        ];
    }
}