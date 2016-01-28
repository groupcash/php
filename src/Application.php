<?php
namespace groupcash\php;

class Application {

    /** @var KeyService */
    private $key;

    public function __construct(KeyService $key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function generateKey() {
        return $this->key->generate();
    }

    /**
     * @param string $key
     * @return string
     */
    public function getAddress($key) {
        return $this->key->publicKey($key);
    }

    /**
     * @param string $promise
     * @param string $backerAddress
     * @param int $serialStart
     * @param int $count
     * @param string $key
     * @return array[]
     */
    public function issueCoins($promise, $backerAddress, $serialStart, $count, $key) {
        $coins = [];
        for ($i = $serialStart; $i < $serialStart + $count; $i++) {
            $coins[] = $this->sign([
                'promise' => $promise,
                'serial' => $i,
                'backer' => $backerAddress,
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
     * @param string $newOwnerAddress
     * @param string $key
     * @return array
     */
    public function transferCoin(array $coin, $newOwnerAddress, $key) {
        return $this->sign([
            'coin' => $coin,
            'owner' => $newOwnerAddress
        ], $key);
    }

    /**
     * @param array $coin
     * @param string $validatedOwnerAddress
     * @param string $key
     * @return array
     * @throws \Exception if invalid
     */
    public function validateTransaction(array $coin, $validatedOwnerAddress, $key) {
        if (!$this->verifySignature($coin)) {
            throw new \Exception('Invalid signature.');
        }

        $newOwner = $coin['content']['owner'];

        if (!isset($coin['content']['coin']['content']['promise'])) {
            if ($coin['content']['coin']['content']['owner'] != $coin['signer']) {
                throw new \Exception('Broken transaction.');
            }

            $coin = $this->validateTransaction($coin['content']['coin'], $validatedOwnerAddress, $key);
        } else if ($coin['content']['owner'] != $validatedOwnerAddress) {
            throw new \Exception('Invalid transaction.');
        } else if ($coin['signer'] != $coin['content']['coin']['content']['backer']) {
            throw new \Exception('Invalid validation.');
        }

        if ($coin['content']['coin']['content']['backer'] != $this->key->publicKey($key)) {
            throw new \Exception('Invalid key.');
        }

        if (!$this->verifySignature($coin['content']['coin'])) {
            throw new \Exception('Invalid coin.');
        }

        return $this->sign([
            'coin' => $coin['content']['coin'],
            'owner' => $newOwner,
            'prev' => hash('sha256', json_encode($coin))
        ], $key);
    }
}