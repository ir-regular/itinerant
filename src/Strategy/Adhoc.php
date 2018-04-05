<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Adhoc implements StrategyInterface
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
        // @TODO: document this clearly somewhere or provide a better interface for actions

        if ($result !== null) {
            // @TODO: ...remember that "better interface for actions"? like a return type hint?
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
