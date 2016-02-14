<?php
namespace groupcash\php\cli;

abstract class Serializer {

    /**
     * @return string Name of class that is serialized and inflated
     */
    abstract public function serializes();

    /**
     * @return string
     */
    abstract public function objectKey();

    /**
     * @return string
     */
    abstract protected function version();

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
        $serialized = [
            'ver' => $this->version(),
            $this->objectKey() => $this->serializeObject($object)
        ];
        return json_encode($serialized);
    }

    /**
     * @param string $serialized
     * @return object
     * @throws \Exception
     */
    public function inflate($serialized) {
        $array = json_decode($serialized, true);

        if ($array['ver'] != $this->version()) {
            throw new \Exception('Unsupported serialization version');
        }

        if (!array_key_exists($this->objectKey(), $array)) {
            throw new \Exception('Not supported serialization.');
        }

        return $this->inflateObject($array[$this->objectKey()]);
    }
}