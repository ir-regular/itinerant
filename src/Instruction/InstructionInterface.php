<?php

namespace IrRegular\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

interface InstructionInterface
{
    public function apply(NodeAdapterInterface $node): \Generator;
}
