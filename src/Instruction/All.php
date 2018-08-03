<?php

namespace IrRegular\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\Fail;
use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

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
