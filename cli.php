<?php

use groupcash\php\Groupcash;
use groupcash\php\impl\EccKeyService;
use groupcash\php\model\Coin;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\delivery\cli\CliField;
use rtens\domin\delivery\Renderer;
use rtens\domin\Parameter;
use rtens\domin\reflection\GenericMethodAction;
use rtens\domin\reflection\MethodActionGenerator;

require_once __DIR__ . '/vendor/autoload.php';

class Base64Renderer implements Renderer {

    public function handles($value) {
        return $value instanceof Coin;
    }

    /**
     * @param Coin $value
     * @return string
     */
    public function render($value) {
        return (string)base64_encode(serialize($value));
    }
}

class Base64Field implements CliField {

    /**
     * @param string $encoded
     * @return string
     * @throws Exception
     */
    public function decode($encoded) {
        return (string)$this->_decode($encoded);
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
        return unserialize(base64_decode($encoded));
    }
}

CliApplication::run(CliApplication::init(function (CliApplication $app) {
    $groupcash = new Groupcash(new EccKeyService());
    $field = new Base64Field();

    $app->fields->add($field);
    $app->renderers->add(new Base64Renderer());

    (new MethodActionGenerator($app->actions, $app->types, $app->parser))
        ->fromObject($groupcash);
    $app->actions->add('decode', (new GenericMethodAction($field, 'decode', $app->types, $app->parser))
        ->generic()->setCaption('Decode'));
}));