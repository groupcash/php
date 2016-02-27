<?php
namespace groupcash\php\io\cli;

use groupcash\php\io\Serializer;
use groupcash\php\model\Coin;
use rtens\domin\delivery\cli\Console;
use rtens\domin\delivery\Renderer;

class SerializingRenderer implements Renderer {

    /** @var Serializer[] */
    private $serializers;

    /** @var Console */
    private $console;

    /**
     * @param Serializer[] $serializers
     * @param Console $console
     */
    public function __construct($serializers, Console $console) {
        $this->serializers = $serializers;
        $this->console = $console;
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
                $keys = $serializer->getTranscoderKeys();
                $this->console->writeLine('Transcoders: ' . implode(', ', $keys));
                $transcoder = $this->console->read("Transcoder [{$keys[0]}]: ");

                return $serializer->serialize($value, $transcoder);
            }
        }
        throw new \Exception('No serializer found.');
    }
}