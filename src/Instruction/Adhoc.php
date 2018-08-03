<?php

namespace IrRegular\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * @TODO rename
 */
class Adhoc implements InstructionInterface
{
    /** @var array */
    private $fallbackExpression;

    /** @var callable */
    private $action;

    /** @var callable */
    private $isApplicable;

    public function __construct(
        array $fallbackExpression,
        callable $action,
        callable $isApplicable
    ) {
        $this->fallbackExpression = $fallbackExpression;
        $this->action = $action;
        $this->isApplicable = $isApplicable;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        if (($this->isApplicable)($node)) {
            // instruction resolved to applied action
            $result = ($this->action)($node);

            if (!($result instanceof NodeAdapterInterface)) {
                throw new \UnexpectedValueException('Adhoc action result must be a NodeAdapterInterface');
            }

        } else {
            // if action is inapplicable, apply the fallback instead
            $result = yield [$this->fallbackExpression, $node];
        }

        yield $result;
    }
}
