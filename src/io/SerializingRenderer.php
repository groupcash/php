<?php
namespace groupcash\php\io;

use groupcash\php\model\Coin;
use rtens\domin\delivery\Renderer;

class SerializingRenderer implements Renderer {

    /** @var Serializer[] */
    private $serializers;

    /**
     * @param Serializer[] $serializers
     */
    public function __construct($serializers) {
        $this->serializers = $serializers;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function handles($value) {
        foreach ($this->serializers as $serializer) {
            if (is_a($value, $serializer->serializes())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Coin $value
     * @return mixed
     * @throws \Exception
     */
    public function render($value) {
        foreach ($this->serializers as $serializer) {
            if (is_a($value, $serializer->serializes())) {
                return $this->encode($serializer->serialize($value));
            }
        }
        throw new \Exception('No serializer found.');
    }

    /**
     * @param string $serialized
     * @return string
     */
    public function encode($serialized) {
        return base64_encode($serialized);
    }
}