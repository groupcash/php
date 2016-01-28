<?php

use groupcash\php\Application;
use groupcash\php\impl\EccKeyService;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\delivery\cli\CliField;
use rtens\domin\delivery\Renderer;
use rtens\domin\Parameter;
use rtens\domin\reflection\GenericMethodAction;
use rtens\domin\reflection\MethodActionGenerator;

require_once __DIR__ . '/vendor/autoload.php';

class Base64Renderer implements Renderer {

    public function handles($value) {
        return is_array($value) && !empty($value);
    }

    public function render($value) {
        $keys = array_keys($value);
        if (!is_int($keys[0])) {
            $value = [$value];
        }
        return json_encode($value, JSON_PRETTY_PRINT) .
        "\n\n------------------\n\n" .
        implode("\n\n", array_map(function ($array) {
            return base64_encode(json_encode($array));
        }, $value));
    }
}

class Base64Field implements CliField {

    /**
     * @param string $encoded
     * @return string
     * @throws Exception
     */
    public function decode($encoded) {
        return json_encode($this->_decode($encoded), JSON_PRETTY_PRINT);
    }

    /**
     * @param Parameter $parameter
     * @return null|string
     */
    public function getDescription(Parameter $parameter) {
        return null;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        return $parameter->getName() == 'coin';
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return mixed
     */
    public function inflate(Parameter $parameter, $serialized) {
        return $this->_decode($serialized);
    }

    private function _decode($encoded) {
        return json_decode(base64_decode($encoded), true);
    }
}

CliApplication::run(CliApplication::init(function (CliApplication $app) {
    $groupcash = new Application(new EccKeyService());
    $field = new Base64Field();

    $app->fields->add($field);
    $app->renderers->add(new Base64Renderer());

    (new MethodActionGenerator($app->actions, $app->types, $app->parser))
        ->fromObject($groupcash);
    $app->actions->add('decode', (new GenericMethodAction($field, 'decode', $app->types, $app->parser))
        ->generic()->setCaption('Decode'));
}));