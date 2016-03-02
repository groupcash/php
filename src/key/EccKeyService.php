<?php
namespace groupcash\php\key;

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

class EccKeyService implements KeyService {

    private static $SIGNATURE_GLUE = '#';

    /**
     * @return string
     */
    public function generatePrivateKey() {
        $generator = EccFactory::getNistCurves()->generator256();
        $key = $generator->createPrivateKey();

        $serializer = new DerPrivateKeySerializer();
        $serialized = $serializer->serialize($key);

        return $serialized;
    }

    /**
     * @param string $privateKey
     * @return string
     */
    public function publicKey($privateKey) {
        $math = MathAdapterFactory::getAdapter();
        $privateKey = $this->deserializePrivate($privateKey, $math);

        $publicKey = $privateKey->getPublicKey();

        $publicSerializer = new DerPublicKeySerializer();
        $serialized = $publicSerializer->serialize($publicKey);

        return $serialized;
    }

    /**
     * @param string $content
     * @param string $privateKey
     * @return string
     */
    public function sign($content, $privateKey) {
        $math = MathAdapterFactory::getAdapter();
        $privateKey = $this->deserializePrivate($privateKey, $math);

        $rng = RandomGeneratorFactory::getRandomGenerator();

        $hash = $this->hash($content);

        $signer = new Signer($math);
        $signature = $signer->sign($privateKey, $hash, $rng->generate($privateKey->getPoint()->getOrder()));

        return $signature->getR() . self::$SIGNATURE_GLUE . $signature->getS();
    }

    /**
     * @param string $content
     * @param string $publicKey
     * @param string $signature
     * @return bool
     * @throws \Exception
     */
    public function verify($content, $publicKey, $signature) {
        if (!strpos($signature, self::$SIGNATURE_GLUE)) {
            throw new \Exception('Invalid signature.');
        }
        list($r, $s) = explode(self::$SIGNATURE_GLUE, $signature);

        $math = MathAdapterFactory::getAdapter();

        $serializer = new DerPublicKeySerializer($math);
        $publicKey = $this->deserialize($publicKey, $serializer);

        $hash = $this->hash($content);

        $signer = new Signer($math);
        return $signer->verify($publicKey, new Signature($r, $s), $hash);
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