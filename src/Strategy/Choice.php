<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\StrategyStack;

class Choice
{
    private $firstPhase = true;

    /**
     * @var StrategyStack
     */
    private $stack;

    /**
     * @var NodeAdapterInterface
     */
    private $failValue;

    /**
     * @var NodeAdapterInterface
     */
    private $node;

    private $initialStrategy;

    private $alternativeStrategy;

    public function __construct(
        StrategyStack $stack,
        NodeAdapterInterface $failValue,
        NodeAdapterInterface $node,
        $initialStrategy,
        $alternativeStrategy
    ) {
        $this->stack = $stack;
        $this->failValue = $failValue;
        $this->node = $node;
        $this->initialStrategy = $initialStrategy;
        $this->alternativeStrategy = $alternativeStrategy;
    }

    public function __invoke(NodeAdapterInterface $previousResult): ?NodeAdapterInterface
    {
        $result = $this->firstPhase
            ? $this->choice($this->node, $this->initialStrategy, $this->alternativeStrategy)
            : $this->choiceIntermediate($previousResult);

        return $result;
    }

    private function choice(NodeAdapterInterface $previousResult, $s1, $s2)
    {
        $this->stack->pop(); // remove self

        $this->stack->push($s2);
        // re-push self, but next time will execute second phase, see below
        // also note that it's important to have the same number of args because see __invoke signature
        $this->stack->push([$this, null, null]);
        $this->stack->push($s1);

        $this->firstPhase = false;

        return null; // always non-terminal
    }

    private function choiceIntermediate(NodeAdapterInterface $previousResult): ?NodeAdapterInterface
    {
        if ($this->failValue !== $previousResult) {
            $this->stack->pop(); // pop self; $s2 will be auto-popped
            return $previousResult; // pass $s1 result
        }

        return $this->node;
    }
}
