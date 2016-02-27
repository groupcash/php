<?php
namespace groupcash\php\io\cli;

use groupcash\php\io\Serializer;
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
                return $serializer->serialize($value);
            }
        }
        throw new \Exception('No serializer found.');
    }
}