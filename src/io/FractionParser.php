<?php
namespace groupcash\php\io;

use groupcash\php\model\Fraction;

class FractionParser {

    function parse($str) {
        if (!strpos($str,'/')) {
            throw new \Exception('Invalid fraction format.');
        }
        
        $nomDen = explode('/', $str);
        return new Fraction($nomDen[0], $nomDen[1]);
    }
}