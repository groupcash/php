<?php

use groupcash\php\Application;
use groupcash\php\impl\EccKeyService;
use groupcash\php\impl\McryptCryptoService;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\delivery\Renderer;
use rtens\domin\reflection\GenericMethodAction;
use rtens\domin\reflection\MethodActionGenerator;

require_once __DIR__ . '/vendor/autoload.php';

class Base64 implements Renderer {

    /** @var Application */
    private $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function handles($value) {
        return is_array($value);
    }

    public function render($value) {
        return implode("\n\n", array_map(function ($array) {
            return base64_encode(json_encode($array));
        }, $value));
    }

    /**
     * @param string $encoded
     * @return string
     */
    public function decode($encoded) {
        $signedContent = json_decode(base64_decode($encoded), true);
        if (!$this->app->verifySignature($signedContent)) {
            return 'Invalid signature';
        }
        return json_encode($signedContent['content'], JSON_PRETTY_PRINT);
    }
}

CliApplication::run(CliApplication::init(function (CliApplication $app) {
    $groupcash = new Application(new EccKeyService(), new McryptCryptoService());
    $base64 = new Base64($groupcash);

    $app->renderers->add($base64);

    (new MethodActionGenerator($app->actions, $app->types, $app->parser))
        ->fromObject($groupcash);
    $app->actions->add('decode', (new GenericMethodAction($base64, 'decode', $app->types, $app->parser))
        ->generic()->setCaption('Decode'));
}));