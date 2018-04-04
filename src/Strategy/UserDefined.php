<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class UserDefined
{
    /** @var array */
    private $strategy;

    public function __construct(
        array $strategy,
        array $args
    ) {
        $this->strategy = $this->fillPlaceholders($strategy, $args);
    }

    public function __invoke(NodeAdapterInterface $node)
    {
        $result = yield [$this->strategy, $node];
        yield $result;
    }

    private function fillPlaceholders(array $strategy, array $args): array
    {
        // substitute numeric placeholders with the actual arguments
        // @TODO: yep, it's ugly, and it doesn't validate the index
        array_walk_recursive($strategy, function (&$value) use ($args) {
            if (is_numeric($value)) {
                $value = $args[(int)$value];
            }
        });

        return $strategy;
    }
}
