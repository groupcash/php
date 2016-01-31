<?php
namespace groupcash\php\cli;

use groupcash\php\Groupcash;
use groupcash\php\impl\EccKeyService;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\reflection\GenericMethodAction;

class Bootstrapper {

    /** @var Groupcash */
    private $lib;

    public function __construct() {
        $this->lib = new Groupcash(new EccKeyService());
        $this->serializer = new CoinSerializer();
    }

    public function run() {
        CliApplication::run(CliApplication::init(function (CliApplication $app) {
            $this->setUpCliApplication($app);
        }));
    }

    private function setUpCliApplication(CliApplication $app) {
        $app->fields->add(new CoinField($this->serializer));
        $app->renderers->add(new CoinRenderer($this->serializer));

        $this->addLibraryActions($app);
        $this->addDecodeAction($app);
    }

    private function addLibraryActions(CliApplication $app) {
        foreach ((new \ReflectionClass($this->lib))->getMethods() as $method) {
            if (!$method->isPublic() || $method->isConstructor()) {
                continue;
            }
            $app->actions->add($method->getName(),
                new GenericMethodAction($this->lib, $method->getName(), $app->types, $app->parser));
        }
    }

    private function addDecodeAction(CliApplication $app) {
        $app->actions->add('decode', (new GenericMethodAction($this->serializer, 'decode', $app->types, $app->parser))
            ->generic()->setCaption('Decode'));
    }
}