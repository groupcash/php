<?php
namespace groupcash\php\io\cli;

use groupcash\php\io\Serializer;
use rtens\domin\delivery\cli\CliField;
use rtens\domin\Parameter;
use watoki\reflect\type\ClassType;

class SerializingField implements CliField {

    /** @var Serializer */
    private $serializer;

    /**
     * @param Serializer $serializer
     */
    public function __construct(Serializer $serializer) {
        $this->serializer = $serializer;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        $type = $parameter->getType();
        return $type instanceof ClassType && $this->serializer->handles($type->getClass());
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return object
     * @throws \Exception
     */
    public function inflate(Parameter $parameter, $serialized) {
        return $this->serializer->inflate($serialized);
    }

    /**
     * @param Parameter $parameter
     * @return null|string
     */
    public function getDescription(Parameter $parameter) {
        return '(' . implode(', ', $this->serializer->getTranscoderKeys()) . ')';
    }
}