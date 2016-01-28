<?php
namespace groupcash\php\model;

interface Transaction {

    /**
     * @return string
     */
    public function fingerprint();

    /**
     * @return string
     */
    public function getTarget();

    /**
     * @return string
     */
    public function __toString();
}