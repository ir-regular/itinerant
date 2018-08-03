<?php

namespace IrRegular\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\Fail;
use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

class One implements InstructionInterface
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
