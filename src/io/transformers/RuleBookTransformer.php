<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transcoder;
use groupcash\php\io\Transformer;
use groupcash\php\model\RuleBook;
use groupcash\php\model\signing\Binary;

class RuleBookTransformer implements Transformer {

    /**
     * @param string $class
     * @return bool
     */
    public function canTransform($class) {
        return $class == RuleBook::class;
    }

    /**
     * @param RuleBook $object
     * @param Transcoder $transcoder
     * @return array
     */
    public function toArray($object, Transcoder $transcoder) {
        $array = [
            'by' => $transcoder->encode($object->getCurrencyAddress()->getData()),
            'rules' => $object->getRules(),
        ];

        if ($object->getPreviousHash()) {
            $array['prev'] = $transcoder->encode($object->getPreviousHash()->getData());
        }

        $array['sig'] = $object->getSignature();
        return $array;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function hasTransformed($array) {
        return array_key_exists('rules', $array);
    }

    /**
     * @param array $array
     * @param Transcoder $transcoder
     * @return object
     */
    public function toObject($array, Transcoder $transcoder) {
        return new RuleBook(
            new Binary($transcoder->decode($array['by'])), $array['rules'], $array['sig'], array_key_exists('prev', $array)
            ? new Binary($transcoder->decode($array['prev']))
            : null
        );
    }
}