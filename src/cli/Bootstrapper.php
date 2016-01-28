<?php
namespace groupcash\php\cli;

use groupcash\php\Groupcash;
use groupcash\php\impl\EccKeyService;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\reflection\GenericMethodAction;
use rtens\domin\reflection\MethodActionGenerator;

class Bootstrapper {

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
        (new MethodActionGenerator($app->actions, $app->types, $app->parser))
            ->fromObject($this->lib);
    }

    private function addDecodeAction(CliApplication $app) {
        $app->actions->add('decode', (new GenericMethodAction($this->serializer, 'decode', $app->types, $app->parser))
            ->generic()->setCaption('Decode'));
    }
}