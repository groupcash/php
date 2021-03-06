<?php
namespace groupcash\php\algorithms;

use groupcash\php\model\signing\Binary;
use groupcash\php\model\signing\Algorithm;
use Mdanter\Ecc\Crypto\Key\PrivateKeyInterface;
use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;
use Mdanter\Ecc\Crypto\Signature\Signature;
use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\MathAdapterFactory;
use Mdanter\Ecc\Math\MathAdapterInterface;
use Mdanter\Ecc\Message\MessageFactory;
use Mdanter\Ecc\Random\RandomGeneratorFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\PrivateKeySerializerInterface;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\PublicKeySerializerInterface;

class EccAlgorithm implements Algorithm {

    private static $SIGNATURE_GLUE = '#';

    /**
     * @return Binary
     */
    public function generateKey() {
        $generator = EccFactory::getNistCurves()->generator256();
        $key = $generator->createPrivateKey();

        $serializer = new DerPrivateKeySerializer();
        $serialized = $serializer->serialize($key);

        return new Binary($serialized);
    }

    /**
     * @param Binary $key
     * @return Binary
     */
    public function getAddress(Binary $key) {
        $math = MathAdapterFactory::getAdapter();
        $inflatedPrivateKey = $this->deserializePrivate($key->getData(), $math);

        $publicKey = $inflatedPrivateKey->getPublicKey();

        $publicSerializer = new DerPublicKeySerializer();
        $serialized = $publicSerializer->serialize($publicKey);

        return new Binary($serialized);
    }

    /**
     * @param string $content
     * @param Binary $key
     * @return string
     */
    public function sign($content, Binary $key) {
        $math = MathAdapterFactory::getAdapter();
        $inflatedPrivateKey = $this->deserializePrivate($key->getData(), $math);

        $rng = RandomGeneratorFactory::getRandomGenerator();

        $hash = $this->hash($content);

        $signer = new Signer($math);
        $signature = $signer->sign($inflatedPrivateKey, $hash, $rng->generate($inflatedPrivateKey->getPoint()->getOrder()));

        return $signature->getR() . self::$SIGNATURE_GLUE . $signature->getS();
    }

    /**
     * @param string $content
     * @param Binary $address
     * @param string $signature
     * @return bool
     * @throws \Exception
     */
    public function verify($content, Binary $address, $signature) {
        if (!strpos($signature, self::$SIGNATURE_GLUE)) {
            throw new \Exception('Invalid signature.');
        }
        list($r, $s) = explode(self::$SIGNATURE_GLUE, $signature);

        $math = MathAdapterFactory::getAdapter();

        $serializer = new DerPublicKeySerializer($math);
        $inflatedPublicKey = $this->deserialize($address->getData(), $serializer);

        $hash = $this->hash($content);

        $signer = new Signer($math);
        return $signer->verify($inflatedPublicKey, new Signature($r, $s), $hash);
    }

    private function deserializePrivate($privateKey, MathAdapterInterface $math) {
        $serializer = new DerPrivateKeySerializer($math);
        return $this->deserialize($privateKey, $serializer);
    }

    /**
     * @param string $key
     * @param PrivateKeySerializerInterface|PublicKeySerializerInterface $serializer
     * @return PrivateKeyInterface|PublicKeyInterface
     * @throws \Exception
     */
    private function deserialize($key, $serializer) {
        try {
            return $serializer->parse($key);
        } catch (\Exception $e) {
            throw new \Exception('Invalid key.');
        }
    }

    /**
     * @param $content
     * @return int|string
     */
    private function hash($content) {
        $messages = new MessageFactory(MathAdapterFactory::getAdapter());
        return $messages->plaintext($content, 'sha256')->getHash();
    }
}