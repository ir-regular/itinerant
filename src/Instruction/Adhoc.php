<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Adhoc implements InstructionInterface
{
    /** @var array */
    private $instructionIfInapplicable;

    /** @var callable */
    private $action;

    public function __construct(
        array $instructionIfInapplicable,
        callable $action
    ) {
        $this->instructionIfInapplicable = $instructionIfInapplicable;
        $this->action = $action;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        // strategy resolved to applied action
        $result = ($this->action)($node);

        // If result is null, that means action is not applicable to $node

        if ($result !== null) {
            if (!($result instanceof NodeAdapterInterface)) {
                throw new \UnexpectedValueException('Adhoc callable result must be a NodeAdapterInterface');
            }
        } else {
            // if action is inapplicable, apply the fallback strategy instead
            $result = yield [$this->instructionIfInapplicable, $node];
        }

        yield $result;
    }
}
