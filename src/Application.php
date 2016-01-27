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

    private function sign(array $content, $key) {
        return base64_encode(json_encode([
            'content' => $content,
            'signer' => $this->key->publicKey($key),
            'signature' => $this->key->sign(json_encode($content), $key)
        ]));
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
            $coins[] = $this->sign([
                'promise' => $promise,
                'serial' => $i,
                'backer' => $backerPublicKey,
            ], $key);
        }
        return $coins;
    }

    /**
     * @param string $signedContent
     * @return bool
     */
    public function verifySignature($signedContent) {
        $signedContent = $this->decode([$signedContent])[0];

        $content = json_encode($signedContent['content']);
        $signature = $signedContent['signature'];
        $publicKey = $signedContent['signer'];

        return $this->key->verify($content, $signature, $publicKey);
    }

    /**
     * @param string[] $coins
     * @param string $newOwnerPublicKey
     * @param string $key
     * @param null|string $passPhrase
     * @return string[]
     */
    public function transferCoins(array $coins, $newOwnerPublicKey, $key, $passPhrase = null) {
        if ($passPhrase) {
            $key = $this->crypto->decrypt($key, $passPhrase);
        }

        $transferred = [];
        foreach ($coins as $coin) {
            $content = [
                'coin' => $coin,
                'to' => $newOwnerPublicKey
            ];
            $transferred[] = $this->sign($content, $key);
        }
        return $transferred;
    }

    /**
     * @param string[] $encoded
     * @return string
     */
    public function decode($encoded) {
        return array_map(function ($encoded) {
            return json_decode(base64_decode($encoded), true);
        }, $encoded);
    }
}