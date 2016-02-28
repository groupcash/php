<?php
namespace groupcash\php\io\cli;

use groupcash\php\Groupcash;
use groupcash\php\io\Serializer;
use groupcash\php\io\transcoders\Base64Transcoder;
use groupcash\php\io\transcoders\JsonTranscoder;
use groupcash\php\io\transcoders\MsgPackTranscoder;
use groupcash\php\io\transformers\AuthorizationTransformer;
use groupcash\php\io\transformers\CoinTransformer;
use groupcash\php\key\EccKeyService;
use rtens\domin\delivery\cli\CliApplication;
use rtens\domin\delivery\cli\Console;
use rtens\domin\reflection\GenericMethodAction;

class Application {

    /** @var Groupcash */
    private $lib;

    /** @var Serializer */
    private $serializer;

    public function __construct() {
        $this->lib = new Groupcash(new EccKeyService());
        $this->serializer = (new Serializer())
            ->addTransformer(new CoinTransformer())
            ->addTransformer(new AuthorizationTransformer());

        if (MsgPackTranscoder::isAvailable()) {
            $this->serializer
                ->registerTranscoder('msgpack64', new Base64Transcoder(new MsgPackTranscoder()))
                ->registerTranscoder('msgpack', new MsgPackTranscoder());
        }

        $this->serializer
            ->registerTranscoder('json64', new Base64Transcoder(new JsonTranscoder()))
            ->registerTranscoder('json', new JsonTranscoder());
    }

    public function run() {
        global $argv;
        $console = new Console($argv);

        CliApplication::run(CliApplication::init(function (CliApplication $app) use ($console) {
            $this->setUpCliApplication($app, $console);
        }), $console);
    }

    private function setUpCliApplication(CliApplication $app, Console $console) {
        $app->fields->add(new SerializingField($this->serializer));
        $app->renderers->add(new SerializingRenderer($this->serializer, $console));
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
            (new GenericMethodAction($this, 'decode', $app->types, $app->parser))->generic()
                ->setCaption('Decode')
                ->setDescription('Displays an object in human-readable form'));
        $app->actions->add('transcode',
            (new GenericMethodAction($this, 'transcode', $app->types, $app->parser))->generic()
                ->setCaption('Transcode')
                ->setDescription('Changes the encoding of an object'));
    }

    /**
     * @param string $encoded
     * @return string
     * @throws \Exception
     */
    public function decode($encoded) {
        return json_encode($this->serializer->decode($encoded), JSON_PRETTY_PRINT);
    }

    /**
     * @param string $encoded
     * @return object
     */
    public function transcode($encoded) {
        return $this->serializer->inflate($encoded);
    }
}