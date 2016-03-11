<?php
namespace groupcash\php\io;

use groupcash\php\model\value\Fraction;

class FractionParser {

    function parse($str) {
        if (is_numeric($str)) {
            $den = 1;
            $product = $str * $den;
            while (intval($product) != $product || strpos(strval($product), '.') !== false) {
                if ($den > PHP_INT_MAX) {
                    throw new \Exception('Maximum precision of 1/' . PHP_INT_MAX . ' exceeded.');
                }
                $den *= 10;
                $product = $str * $den;
            }
            return new Fraction($product, $den);
        }
        if (strpos($str, '/')) {
            $nomDen = explode('/', $str);
            return new Fraction($nomDen[0], $nomDen[1]);
        }

        throw new \Exception('Invalid fraction format.');
    }
}