<?php
namespace groupcash\php\model;

use groupcash\php\KeyService;

class Signer {

    /** @var KeyService */
    private $service;

    /** @var string */
    private $key;

    /**
     * @param KeyService $service
     * @param string $key
     */
    public function __construct(KeyService $service, $key) {
        $this->key = $key;
        $this->service = $service;
    }

    /**
     * @param mixed|Finger $content
     * @return Signature
     */
    public function sign($content) {
        $fingerprint = self::squash($content);
        $hash = $this->service->hash($fingerprint);
        $sign = $this->service->sign($hash, $this->key);
        
        return new Signature($this->getAddress(), $sign);
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->service->publicKey($this->key);
    }

    /**
     * @param mixed|Finger $content
     * @return string
     */
    public static function squash($content) {
        if ($content instanceof Finger) {
            return self::squash($content->getPrint());
        } else if (is_array($content)) {
            return implode("\0", array_map(function ($item) {
                return self::squash($item);
            }, $content));
        } else {
            return (string)$content;
        }
    }
}