<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Id implements StrategyInterface
{
    public function apply(NodeAdapterInterface $node): \Generator
    {
        yield $node;
    }
}
