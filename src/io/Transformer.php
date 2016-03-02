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
     * @param Transcoder $transcoder
     * @return array
     */
    public function toArray($object, Transcoder $transcoder);

    /**
     * @param array $array
     * @return bool
     */
    public function hasTransformed($array);

    /**
     * @param array $array
     * @param Transcoder $transcoder
     * @return object
     */
    public function toObject($array, Transcoder $transcoder);
}