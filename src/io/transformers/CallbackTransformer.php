<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transformer;

class CallbackTransformer extends Transformer {

    /** @var string */
    private $class;

    /** @var string */
    private $token;

    /** @var callable */
    private $toObject;

    /** @var callable */
    private $toArray;

    /**
     * @param string $class
     * @param string $token
     * @param callable $toArray
     * @param callable $toObject
     */
    public function __construct($class, $token, callable $toArray, callable $toObject) {
        $this->class = $class;
        $this->token = $token;
        $this->toObject = $toObject;
        $this->toArray = $toArray;
    }

    /**
     * @return string Name of class that is serialized and inflated
     */
    public function transforms() {
        return $this->class;
    }

    /**
     * @return string
     */
    protected function token() {
        return $this->token;
    }

    /**
     * @param array $array
     * @return object
     */
    protected function toObject($array) {
        return call_user_func($this->toObject, $array);
    }

    /**
     * @param object $object
     * @return array
     */
    protected function toArray($object) {
        return call_user_func($this->toArray, $object);
    }
}