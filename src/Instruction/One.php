<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class One implements StrategyInterface
{
    /** @var array */
    private $childInstruction;

    public function __construct(
        array $childInstruction
    ) {
        $this->childInstruction = $childInstruction;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        // if $node has no children: fail by default
        $result = Fail::fail();

        $unprocessed = $node->getChildren();

        if ($unprocessed->valid()) {
            foreach ($unprocessed as $child) {
                $result = yield [$this->childInstruction, $child];

                if (Fail::fail() !== $result) {
                    break;
                }
            }
        }

        yield $result;
    }
}
