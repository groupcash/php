<?php
namespace groupcash\php\io;

interface Transformer {

    /**
     * @param string $class
     * @return bool
     */
    public function canTransform($class);

    /**
     * @param object $object
     * @return array
     */
    public function toArray($object);

    /**
     * @param array $array
     * @return bool
     */
    public function hasTransformed($array);

    /**
     * @param array $array
     * @return object
     */
    public function toObject($array);
}