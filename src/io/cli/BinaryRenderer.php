<?php
namespace groupcash\php\io\cli;

use groupcash\php\io\Transcoder;
use groupcash\php\key\Binary;
use rtens\domin\delivery\cli\Console;
use rtens\domin\delivery\Renderer;

class BinaryRenderer implements Renderer {

    /** @var Console */
    private $console;

    /** @var Transcoder[] */
    private $transcoders;

    /**
     * @param Console $console
     * @param Transcoder[] $transcoders with keys
     */
    public function __construct(Console $console, array $transcoders) {
        $this->console = $console;
        $this->transcoders = $transcoders;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function handles($value) {
        return $value instanceof Binary;
    }

    /**
     * @param Binary $value
     * @return mixed
     */
    public function render($value) {
        return $this->transcoders[$this->getTranscoderKey()]->encode($value->getData());
    }

    /**
     * @return string
     */
    private function getTranscoderKey() {
        $keys = array_keys($this->transcoders);

        if ($this->console->getArguments() == ['!']) {
            $this->console->writeLine('Available encodings: ' . implode(', ', $keys));
            return $this->console->read("Encoding [{$keys[0]}]: ") ?: $keys[0];
        }

        try {
            return $this->console->getOption('encoding');
        } catch (\Exception $e) {
            return $keys[0];
        }
    }
}