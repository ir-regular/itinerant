<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

interface StrategyInterface
{
    public function apply(NodeAdapterInterface $node): \Generator;
}
