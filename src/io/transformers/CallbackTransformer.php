<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transformer;

class CallbackTransformer implements Transformer {

    private $canTransform;
    private $toArray;
    private $hasTransformed;
    private $toObject;

    /**
     * @param callable $toArray
     * @param callable $toObject
     */
    public function __construct(callable $toArray, callable $toObject) {
        $this->toArray = $toArray;
        $this->toObject = $toObject;

        $true = function () {
            return true;
        };
        $this->hasTransformed = $true;
        $this->canTransform = $true;
    }

    /**
     * @param callable $canTransform
     * @return CallbackTransformer
     */
    public function setCanTransform(callable $canTransform) {
        $this->canTransform = $canTransform;
        return $this;
    }

    /**
     * @param callable $hasTransformed
     * @return CallbackTransformer
     */
    public function setHasTransformed(callable $hasTransformed) {
        $this->hasTransformed = $hasTransformed;
        return $this;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function canTransform($class) {
        return call_user_func($this->canTransform, $class);
    }

    /**
     * @param object $object
     * @return array
     */
    public function toArray($object) {
        return call_user_func($this->toArray, $object);
    }

    /**
     * @param array $array
     * @return bool
     */
    public function hasTransformed($array) {
        return call_user_func($this->hasTransformed, $array);
    }

    /**
     * @param array $array
     * @return object
     */
    public function toObject($array) {
        return call_user_func($this->toObject, $array);
    }
}