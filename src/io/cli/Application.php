<?php
namespace groupcash\php\io\cli;

use groupcash\php\Groupcash;
use groupcash\php\io\AuthorizationSerializer;
use groupcash\php\io\CoinSerializer;
use groupcash\php\io\transcoders\Base64Transcoder;
use groupcash\php\io\transcoders\JsonTranscoder;
use groupcash\php\io\Serializer;
use groupcash\php\io\Transcoder;
use groupcash\php\key\EccKeyService;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\delivery\cli\Console;
use rtens\domin\reflection\GenericMethodAction;

class Application {

    /** @var Groupcash */
    private $lib;

    /** @var Serializer[] */
    private $serializers;

    /** @var Transcoder[] */
    private $transcoders;

    public function __construct() {
        $this->lib = new Groupcash(new EccKeyService());
        $this->transcoders = [
            'json' => new JsonTranscoder(),
            'json64' => new Base64Transcoder(new JsonTranscoder())
        ];
        $this->serializers = [
            new CoinSerializer($this->transcoders),
            new AuthorizationSerializer($this->transcoders)
        ];
    }

    public function run() {
        global $argv;
        $console = new Console($argv);

        CliApplication::run(CliApplication::init(function (CliApplication $app) use ($console) {
            $this->setUpCliApplication($app, $console);
        }), $console);
    }

    private function setUpCliApplication(CliApplication $app, Console $console) {
        $app->fields->add(new SerializingField($this->serializers));
        $app->renderers->add(new SerializingRenderer($this->serializers, $console));
        $app->renderers->add(new ArrayRenderer($app->renderers));

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
        $app->actions->add('decode',
            (new GenericMethodAction($this, 'decode', $app->types, $app->parser))
                ->generic()->setCaption('Decode'));
    }

    /**
     * @param string $encoded
     * @return mixed
     * @throws \Exception
     */
    public function decode($encoded) {
        foreach ($this->transcoders as $transcoder) {
            if ($transcoder->hasEncoded($encoded)) {
                return json_encode($transcoder->decode($encoded)[1], JSON_PRETTY_PRINT);
            }
        }
        throw new \Exception('Could not find transcoder');
    }
}