<?php
namespace groupcash\php;

class Application {

    /** @var KeyService */
    private $key;

    /** @var CryptoService */
    private $crypto;

    public function __construct(KeyService $key, CryptoService $crypto) {
        $this->key = $key;
        $this->crypto = $crypto;
    }

    /**
     * @param null|string $passPhrase
     * @return string
     */
    public function generateKey($passPhrase = null) {
        $privateKey = $this->key->generate();

        $key = $privateKey;
        if ($passPhrase) {
            $key = $this->crypto->encrypt($key, $passPhrase);
        }
        return [
            'private' => $key,
            'public' => $this->key->publicKey($privateKey)
        ];
    }

    /**
     * @param string $promise
     * @param string $backerPublicKey
     * @param int $serialStart
     * @param int $count
     * @param string $key
     * @param null|string $passPhrase
     * @return \string[]
     */
    public function issueCoins($promise, $backerPublicKey, $serialStart, $count, $key, $passPhrase = null) {
        if ($passPhrase) {
            $key = $this->crypto->decrypt($key, $passPhrase);
        }

        $coins = [];
        for ($i = $serialStart; $i < $serialStart + $count; $i++) {
            $content = [
                'promise' => $promise,
                'serial' => $i,
                'backer' => $backerPublicKey,
                'issuer' => $this->key->publicKey($key)
            ];
            $coins[] = json_encode([
                'content' => $content,
                'signature' => $this->key->sign(json_encode($content), $key)
            ]);
        }
        return $coins;
    }

    /**
     * @param string $signedContent
     * @param string $publicKey
     * @return bool
     */
    public function verifySignature($signedContent, $publicKey) {
        $signedContent = json_decode($signedContent, true);
        $content = json_encode($signedContent['content']);
        $signature = $signedContent['signature'];

        return $this->key->verify($content, $signature, $publicKey);
    }
}