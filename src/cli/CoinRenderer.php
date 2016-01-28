<?php
namespace groupcash\php\cli;

use groupcash\php\model\Coin;
use rtens\domin\delivery\Renderer;

class CoinRenderer implements Renderer {

    /** @var CoinSerializer */
    private $serializer;

    public function __construct(CoinSerializer $serializer) {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function handles($value) {
        return $value instanceof Coin;
    }

    /**
     * @param Coin $value
     * @return mixed
     */
    public function render($value) {
        return $this->serializer->serialize($value);
    }
}