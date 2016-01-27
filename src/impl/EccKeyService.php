<?php
namespace groupcash\php\impl;

use groupcash\php\KeyService;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;

class EccKeyService implements KeyService {

    /**
     * @return string
     */
    public function generate() {
        $generator = EccFactory::getNistCurves()->generator256();
        $key = $generator->createPrivateKey();

        $serializer = new DerPrivateKeySerializer();
        $serialized = $serializer->serialize($key);

        return base64_encode($serialized);
    }
}