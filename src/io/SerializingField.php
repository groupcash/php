<?php
namespace groupcash\php\io;

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
        $decoded = $this->decode($serialized);
        foreach ($this->serializers as $serializer) {
            if ($serializer->inflates($decoded)) {
                return $serializer->inflate($decoded);
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

    /**
     * Presents a coin in a human-readable format.
     *
     * @param string $encoded
     * @param bool $pretty
     * @return string
     */
    public function decode($encoded, $pretty = false) {
        $decoded = base64_decode($encoded);
        if (!$pretty) {
            return $decoded;
        }

        $decoded = substr($decoded, strpos($decoded, '{'));
        return json_encode(json_decode($decoded, true), JSON_PRETTY_PRINT);
    }
}