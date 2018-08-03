<?php

namespace IrRegular\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\Fail;
use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

class Choice implements InstructionInterface
{
    /** @var array */
    private $initialExpression;

    /** @var array */
    private $alternativeExpression;

    public function __construct(
        array $initialExpression,
        array $alternativeExpression
    ) {
        $this->initialExpression = $initialExpression;
        $this->alternativeExpression = $alternativeExpression;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        $result = yield [$this->initialExpression, $node];

        if (Fail::fail() === $result) {
            $result = yield [$this->alternativeExpression, $node];
        }

        yield $result;
    }
}
