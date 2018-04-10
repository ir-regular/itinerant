<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class All implements InstructionInterface
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
        // if $node has no children: return $node by default
        $result = $node;

        $unprocessed = $node->getChildren();
        $processed = [];

        if ($unprocessed->valid()) {
            foreach ($unprocessed as $child) {
                $result = yield [$this->childInstruction, $child];

                if (Fail::fail() === $result) {
                    break;
                }

                $processed[] = $result;
            }

            if (Fail::fail() !== $result) {
                $node->setChildren($processed);
                $result = $node;
            }
        }

        yield $result;
    }
}
