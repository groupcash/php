<?php
namespace groupcash\php\model;

class Promise implements Transaction{

    /** @var string */
    private $description;

    /** @var int */
    private $serial;

    /** @var string */
    private $backer;

    /**
     * @param string $backer
     * @param string $description
     * @param int $serial
     */
    public function __construct($backer, $description, $serial) {
        $this->backer = $backer;
        $this->description = $description;
        $this->serial = $serial;
    }

    /**
     * @return string
     */
    public function getBacker() {
        return $this->backer;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getSerial() {
        return $this->serial;
    }

    /**
     * @return string
     */
    public function getTarget() {
        return $this->getBacker();
    }

    /**
     * @return string
     */
    public function fingerprint() {
        return implode("", [$this->description, $this->serial, $this->backer]);
    }

    /**
     * @return string
     */
    public function __toString() {
        return "{$this->description}({$this->serial}) by {$this->backer}";
    }
}