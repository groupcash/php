<?php
namespace groupcash\php\io\cli;

use groupcash\php\io\Serializer;
use groupcash\php\model\Coin;
use rtens\domin\delivery\cli\CliField;
use rtens\domin\Parameter;
use watoki\reflect\type\ClassType;

class SerializingField implements CliField {

    /** @var Serializer[] */
    private $serializers;

    /**
     * @param Serializer[] $serializers
     */
    public function __construct($serializers) {
        $this->serializers = $serializers;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        foreach ($this->serializers as $serializer) {
            if ($parameter->getType() == new ClassType($serializer->serializes())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return Coin
     * @throws \Exception
     */
    public function inflate(Parameter $parameter, $serialized) {
        foreach ($this->serializers as $serializer) {
            if ($serializer->inflates($serialized)) {
                return $serializer->inflate($serialized);
            }
        }
        throw new \Exception('No serializer found.');
    }

    /**
     * @param Parameter $parameter
     * @return null|string
     */
    public function getDescription(Parameter $parameter) {
        return null;
    }
}