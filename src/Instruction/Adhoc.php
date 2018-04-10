<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * @TODO rename
 * @TODO add optional argument ?callable $isApplicable = null (stop returning null if not applicable)
 */
class Adhoc implements InstructionInterface
{
    /** @var array */
    private $fallbackExpression;

    /** @var callable */
    private $action;

    public function __construct(
        array $fallbackExpression,
        callable $action
    ) {
        $this->fallbackExpression = $fallbackExpression;
        $this->action = $action;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        // instruction resolved to applied action
        $result = ($this->action)($node);

        // If result is null, that means action is not applicable to $node

        if ($result !== null) {
            if (!($result instanceof NodeAdapterInterface)) {
                throw new \UnexpectedValueException('Adhoc callable result must be a NodeAdapterInterface');
            }
        } else {
            // if action is inapplicable, apply the fallback instead
            $result = yield [$this->fallbackExpression, $node];
        }

        yield $result;
    }
}
