<?php
namespace groupcash\php\io;

class Serializer {

    /** @var Transcoder[] indexed by key */
    private $transcoders = [];

    /** @var Transformer[] */
    private $transformers = [];

    /**
     * @param string $key
     * @param Transcoder $transcoder
     * @return Serializer
     */
    public function registerTranscoder($key, Transcoder $transcoder) {
        $this->transcoders[$key] = $transcoder;
        return $this;
    }

    /**
     * @param Transformer $transformer
     * @return Serializer
     */
    public function addTransformer(Transformer $transformer) {
        $this->transformers[] = $transformer;
        return $this;
    }

    /**
     * @param string|object $classOrObject
     * @return bool
     */
    public function handles($classOrObject) {
        $class = is_object($classOrObject) ? get_class($classOrObject) : $classOrObject;

        foreach ($this->transformers as $transformer) {
            if ($transformer->canTransform($class)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param object $object
     * @param string $transcoderKey
     * @return string
     */
    public function serialize($object, $transcoderKey) {
        $transcoder = $this->getTranscoder($transcoderKey);
        $transformer = $this->getTransformerForObject($object);

        return $transcoder->encode($transformer->toArray($object, $transcoder->getBinaryTranscoder()));
    }

    /**
     * @param string $serialized
     * @return object
     * @throws \Exception
     */
    public function inflate($serialized) {
        $array = $this->decode($serialized);
        $transformer = $this->getTransformerForArray($array);
        return $transformer->toObject($array, $this->getTranscoderForString($serialized)->getBinaryTranscoder());
    }

    /**
     * @param string $encoded
     * @return array
     * @throws \Exception
     */
    public function decode($encoded) {
        $transcoder = $this->getTranscoderForString($encoded);
        return $transcoder->decode($encoded);
    }

    private function getTranscoder($transcoderKey) {
        if (!array_key_exists($transcoderKey, $this->transcoders)) {
            throw new \Exception("Transcoder not registered: [$transcoderKey]");
        }

        return $this->transcoders[$transcoderKey];
    }

    public function getTranscoderKeys() {
        return array_keys($this->transcoders);
    }

    private function getTransformerForObject($object) {
        foreach ($this->transformers as $transformer) {
            if ($transformer->canTransform(get_class($object))) {
                return $transformer;
            }
        }
        throw new \Exception('Not transformer registered for [' . get_class($object) . '].');
    }

    private function getTranscoderForString($string) {
        foreach ($this->transcoders as $transcoder) {
            if ($transcoder->hasEncoded($string)) {
                return $transcoder;
            }
        }
        throw new \Exception('No matching transcoder registered.');
    }

    private function getTransformerForArray($array) {
        foreach ($this->transformers as $transformer) {
            if ($transformer->hasTransformed($array)) {
                return $transformer;
            }
        }
        throw new \Exception('New matching transformer available.');
    }
}