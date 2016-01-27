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
        return $key;
    }

    /**
     * @param string $key
     * @param null|string $passPhrase
     * @return string
     */
    public function publicKey($key, $passPhrase = null) {
        if ($passPhrase) {
            $key = $this->crypto->decrypt($key, $passPhrase);
        }

        return $this->key->publicKey($key);
    }

    /**
     * @param string $promise
     * @param string $backerPublicKey
     * @param int $serialStart
     * @param int $count
     * @param string $key
     * @param null|string $passPhrase
     * @return array[]
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

    private function sign(array $content, $key) {
        return [
            'content' => $content,
            'signer' => $this->key->publicKey($key),
            'signature' => $this->key->sign(json_encode($content), $key)
        ];
    }

    /**
     * @param array $signedContent
     * @return bool
     */
    public function verifySignature(array $signedContent) {
        $content = $signedContent['content'];
        $signature = $signedContent['signature'];
        $publicKey = $signedContent['signer'];

        return $this->key->verify(json_encode($content), $signature, $publicKey);
    }

    /**
     * @param array $coin
     * @param string $newOwnerPublicKey
     * @param string $key
     * @param null|string $passPhrase
     * @return array
     */
    public function transferCoin($coin, $newOwnerPublicKey, $key, $passPhrase = null) {
        if ($passPhrase) {
            $key = $this->crypto->decrypt($key, $passPhrase);
        }

        return $this->sign([
            'coin' => $coin,
            'to' => $newOwnerPublicKey
        ], $key);
    }

    /**
     * @param array $coin
     * @param string $ownerPublicKey
     * @param string $key
     * @param null|string $passPhrase
     * @return array
     * @throws \Exception if invalid
     */
    public function validateTransaction(array $coin, $ownerPublicKey, $key, $passPhrase = null) {
        if (!$this->verifySignature($coin)) {
            throw new \Exception('Invalid signature.');
        }

        $newOwner = $coin['content']['to'];

        if (!isset($coin['content']['coin']['content']['promise'])) {
            if ($coin['content']['coin']['content']['to'] != $coin['signer']) {
                throw new \Exception('Broken transaction.');
            }

            $coin = $this->validateTransaction($coin['content']['coin'], $ownerPublicKey, $key, $passPhrase);
        } else if ($coin['content']['to'] != $ownerPublicKey) {
            throw new \Exception('Invalid transaction.');
        } else if ($coin['signer'] != $coin['content']['coin']['content']['backer']) {
            throw new \Exception('Invalid validation.');
        }

        if ($passPhrase) {
            $key = $this->crypto->decrypt($key, $passPhrase);
        }

        if ($coin['content']['coin']['content']['backer'] != $this->key->publicKey($key)) {
            throw new \Exception('Invalid key.');
        }

        if (!$this->verifySignature($coin['content']['coin'])) {
            throw new \Exception('Invalid coin.');
        }

        return $this->sign([
            'coin' => $coin['content']['coin'],
            'to' => $newOwner,
            'prev' => hash('sha256', json_encode($coin))
        ], $key);
    }
}