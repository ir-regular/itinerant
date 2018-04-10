<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Choice implements StrategyInterface
{
    /** @var array */
    private $initialInstruction;

    /** @var array */
    private $alternativeInstruction;

    public function __construct(
        array $initialInstruction,
        array $alternativeInstruction
    ) {
        $this->initialInstruction = $initialInstruction;
        $this->alternativeInstruction = $alternativeInstruction;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        $result = yield [$this->initialInstruction, $node];

        if (Fail::fail() === $result) {
            $result = yield [$this->alternativeInstruction, $node];
        }

        yield $result;
    }
}
