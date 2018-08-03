<?php

namespace IrRegular\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\Fail;
use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

class Seq implements InstructionInterface
{
    /** @var array */
    private $initialInstruction;

    /** @var array */
    private $followupInstruction;

    public function __construct(
        array $initialInstruction,
        array $followupInstruction
    ) {
        $this->initialInstruction = $initialInstruction;
        $this->followupInstruction = $followupInstruction;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        $result = yield [$this->initialInstruction, $node];

        if (Fail::fail() !== $result) {
            $result = yield [$this->followupInstruction, $result];
        }

        yield $result;
    }
}
