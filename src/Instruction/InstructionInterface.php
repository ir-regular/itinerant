<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

interface InstructionInterface
{
    public function apply(NodeAdapterInterface $node): \Generator;
}
