<?php
namespace groupcash\php\model\signing;

class Binary implements Finger {

    /** @var mixed Binary data */
    private $data;

    /**
     * @param mixed $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return $this->data;
    }

    function __toString() {
        return base64_encode($this->data);
    }
}