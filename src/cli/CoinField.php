<?php
namespace groupcash\php\cli;

use groupcash\php\model\Coin;
use rtens\domin\delivery\cli\CliField;
use rtens\domin\Parameter;
use watoki\reflect\type\ClassType;

class CoinField implements CliField {

    /** @var CoinSerializer */
    private $serializer;

    public function __construct(CoinSerializer $serializer) {
        $this->serializer = $serializer;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        return $parameter->getType() == new ClassType(Coin::class);
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return Coin
     */
    public function inflate(Parameter $parameter, $serialized) {
        return $this->serializer->unserialize($serialized);
    }

    /**
     * @param Parameter $parameter
     * @return null|string
     */
    public function getDescription(Parameter $parameter) {
        return null;
    }
}