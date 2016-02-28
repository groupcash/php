<?php
namespace groupcash\php\io;

abstract class Transformer {

    /**
     * @return string Name of class that is serialized and inflated
     */
    abstract public function transforms();

    /**
     * @return string
     */
    abstract protected function token();

    /**
     * @param array $array
     * @return object
     */
    abstract protected function toObject($array);

    /**
     * @param object $object
     * @return array
     */
    abstract protected function toArray($object);

    /**
     * @param object $object
     * @return array
     */
    public function objectToArray($object) {
        return [$this->token(), $this->toArray($object)];
    }

    /**
     * @param array $array
     * @return bool
     */
    public function matches($array) {
        return $array[0] == $this->token();
    }

    /**
     * @param array $array
     * @return object
     * @throws \Exception
     */
    public function arrayToObject($array) {
        if (!$this->matches($array)) {
            throw new \Exception('Unsupported transformation.');
        }

        return $this->toObject($array[1]);
    }
}