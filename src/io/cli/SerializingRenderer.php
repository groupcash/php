<?php
namespace groupcash\php\io\cli;

use groupcash\php\io\Serializer;
use groupcash\php\model\Coin;
use rtens\domin\delivery\cli\Console;
use rtens\domin\delivery\Renderer;

class SerializingRenderer implements Renderer {

    /** @var Serializer */
    private $serializer;

    /** @var Console */
    private $console;

    /**
     * @param Serializer $serializer
     * @param Console $console
     */
    public function __construct($serializer, Console $console) {
        $this->serializer = $serializer;
        $this->console = $console;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function handles($value) {
        return $this->serializer->handles($value);
    }

    /**
     * @param Coin $value
     * @return mixed
     * @throws \Exception
     */
    public function render($value) {
        $keys = $this->serializer->getTranscoderKeys();
        $this->console->writeLine('Transcoders: ' . implode(', ', $keys));
        $transcoder = $this->console->read("Transcoder [{$keys[0]}]: ");

        return $this->serializer->serialize($value, $transcoder);
    }
}