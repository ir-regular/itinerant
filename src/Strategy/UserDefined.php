<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class UserDefined implements StrategyInterface
{
    /** @var array */
    private $instruction;

    public function __construct(
        array $instruction,
        array $args
    ) {
        $this->instruction = $this->substitutePlaceholders($instruction, $args);
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        $result = yield [$this->instruction, $node];
        yield $result;
    }

    private function substitutePlaceholders(array $instruction, array $args): array
    {
        // substitute numeric placeholders with the actual arguments
        // @TODO: yep, it's ugly, and it doesn't validate the index
        array_walk_recursive($instruction, function (&$value) use ($args) {
            if (is_numeric($value)) {
                $value = $args[(int)$value];
            }
        });

        return $instruction;
    }
}
