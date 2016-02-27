<?php
namespace groupcash\php\io;

abstract class Serializer {

    /** @var Transcoder[] indexed by key */
    private $transcoders;

    /**
     * @param Transcoder[] $transcoders
     */
    public function __construct(array $transcoders) {
        $this->transcoders = $transcoders;
    }

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
     * @param string $transcoder Name of transcoder class
     * @return string
     */
    public function serialize($object, $transcoder = null) {
        return $this->getTranscoder($transcoder)->encode([$this->token(), $this->serializeObject($object)]);
    }

    /**
     * @param string $serialized
     * @return bool
     */
    public function inflates($serialized) {
        foreach ($this->transcoders as $transcoder) {
            if ($transcoder->hasEncoded($serialized)) {
                return $transcoder->decode($serialized)[0] == $this->token();
            }
        }
        return false;
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

        foreach ($this->transcoders as $transcoder) {
            if ($transcoder->hasEncoded($serialized)) {
                $decoded = $transcoder->decode($serialized);
                return $this->inflateObject($decoded[1]);
            }
        }

        throw new \Exception('No matching transcoder registered');
    }

    private function getTranscoder($transcoderKey = null) {
        if (!$transcoderKey && $this->transcoders) {
            return array_values($this->transcoders)[0];
        }

        if (array_key_exists($transcoderKey, $this->transcoders)) {
            return $this->transcoders[$transcoderKey];
        }
        throw new \Exception("Transcoder not registered [$transcoderKey]");
    }

    public function getTranscoderKeys() {
        return array_keys($this->transcoders);
    }
}