<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class UserDefined
{
    /**
     * @var array
     */
    private $strategy;

    /**
     * @var NodeAdapterInterface
     */
    private $node;

    public function __construct(
        $strategy,
        $args,
        NodeAdapterInterface $node
    ) {
        $this->strategy = $this->fillPlaceholders($strategy, $args);
        $this->node = $node;
    }

    public function __invoke()
    {
        return [[$this->strategy, $this->node]];
    }

    private function fillPlaceholders($strategy, $args)
    {
        // substitute numeric placeholders with the actual arguments
        // @TODO: yep, it's ugly, and it doesn't validate the index
        array_walk_recursive($strategy, function (&$value) use ($args) {
            if (is_numeric($value)) {
                $value = $args[(int) $value];
            }
        });

        return $strategy;
    }
}
