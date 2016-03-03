<?php
namespace groupcash\php\model\signing;

class Signer {

    /** @var Algorithm */
    private $service;

    /** @var Binary */
    private $key;

    /**
     * @param Algorithm $service
     * @param Binary $key
     */
    public function __construct(Algorithm $service, Binary $key) {
        $this->key = $key;
        $this->service = $service;
    }

    /**
     * @param mixed|Finger $content
     * @return string
     */
    public function sign($content) {
        return $this->service->sign(self::squash($content), $this->key);
    }

    /**
     * @return Binary
     */
    public function getAddress() {
        return $this->service->getAddress($this->key);
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