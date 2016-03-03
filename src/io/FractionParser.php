<?php
namespace groupcash\php\io;

use groupcash\php\model\value\Fraction;

class FractionParser {

    function parse($str) {
        if (is_numeric($str)) {
            $den = 1;
            while (intval($str * $den) != $str * $den) {
                $den *= 10;
            }
            return new Fraction($str * $den, $den);
        } if (strpos($str,'/')) {
            $nomDen = explode('/', $str);
            return new Fraction($nomDen[0], $nomDen[1]);
        }

        throw new \Exception('Invalid fraction format.');
    }
}