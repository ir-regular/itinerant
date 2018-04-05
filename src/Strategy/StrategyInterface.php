<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

interface StrategyInterface
{
    public function apply(NodeAdapterInterface $node): \Generator;
}
