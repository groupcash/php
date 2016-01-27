<?php
namespace groupcash\php\impl;

use groupcash\php\KeyService;
use Mdanter\Ecc\Crypto\Signature\Signature;
use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\MathAdapterFactory;
use Mdanter\Ecc\Message\MessageFactory;
use Mdanter\Ecc\Random\RandomGeneratorFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;

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

    /**
     * @param string $key
     * @return string
     */
    public function publicKey($key) {
        $serializer = new DerPrivateKeySerializer();
        $key = $serializer->parse(base64_decode($key));

        $publicKey = $key->getPublicKey();

        $publicSerializer = new DerPublicKeySerializer();
        $serialized = $publicSerializer->serialize($publicKey);

        return base64_encode($serialized);
    }

    /**
     * @param string $content
     * @param string $key
     * @return string
     */
    public function sign($content, $key) {
        $math = MathAdapterFactory::getAdapter();

        $serializer = new DerPrivateKeySerializer($math);
        $key = $serializer->parse(base64_decode($key));

        $rng = RandomGeneratorFactory::getRandomGenerator();

        $messages = new MessageFactory($math);
        $hash = $messages->plaintext($content, 'sha256')->getHash();

        $signer = new Signer($math);
        $signature = $signer->sign($key, $hash, $rng->generate($key->getPoint()->getOrder()));

        return [$signature->getR(), $signature->getS()];
    }

    /**
     * @param string $content
     * @param string $signature
     * @param string $publicKey
     * @return boolean
     */
    public function verify($content, $signature, $publicKey) {
        list($r, $s) = $signature;

        $math = MathAdapterFactory::getAdapter();

        $serializer = new DerPublicKeySerializer($math);
        $publicKey = $serializer->parse(base64_decode($publicKey));

        $messages = new MessageFactory($math);
        $hash = $messages->plaintext($content, 'sha256')->getHash();

        $signer = new Signer($math);
        return $signer->verify($publicKey, new Signature($r, $s), $hash);
    }
}