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
        NodeAdapterInterface $node = null
    ) {
        $this->strategy = $this->fillPlaceholders($strategy, $args);

        if ($node) {
            $this->node = $node;
        }
    }

    public function __invoke(NodeAdapterInterface $node)
    {
        return [[$this->strategy, $this->node ?: $node]];
    }

    private function fillPlaceholders($strategy, $args)
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
