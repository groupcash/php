<?php
namespace groupcash\php\model\signing;

interface Finger {

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint();
}