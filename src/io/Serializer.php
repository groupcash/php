<?php
namespace groupcash\php\io;

abstract class Serializer {

    /**
     * @return string Name of class that is serialized and inflated
     */
    abstract public function serializes();

    /**
     * @return string
     */
    abstract protected function token();

    /**
     * @param array $serialized
     * @return object
     */
    abstract protected function inflateObject($serialized);

    /**
     * @param object $object
     * @return array
     */
    abstract protected function serializeObject($object);

    /**
     * @param object $object
     * @return string
     */
    public function serialize($object) {
        return $this->token() . json_encode($this->serializeObject($object));
    }

    /**
     * @param string $serialized
     * @return bool
     */
    public function inflates($serialized) {
        $serializerId = substr($serialized, 0, strlen($this->token()));
        return $serializerId == $this->token();
    }

    /**
     * @param string $serialized
     * @return object
     * @throws \Exception
     */
    public function inflate($serialized) {
        if (!$this->inflates($serialized)) {
            throw new \Exception('Unsupported serialization.');
        }
        return $this->inflateObject(json_decode(substr($serialized, strlen($this->token())), true));
    }
}