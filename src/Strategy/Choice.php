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

    public function __construct(StrategyStack $stack, NodeAdapterInterface $failValue)
    {
        $this->stack = $stack;
        $this->failValue = $failValue;
    }

    public function __invoke(NodeAdapterInterface $previousResult, $s1, $s2): ?NodeAdapterInterface
    {
        $result = $this->firstPhase
            ? $this->choice($previousResult, $s1, $s2)
            : $this->choiceIntermediate($previousResult);

        return $result;
    }

    private function choice(NodeAdapterInterface $previousResult, $s1, $s2)
    {
        $this->stack->pop(); // remove self

        $this->stack->push($s2, $this->failValue);
        // re-push self, but next time will execute second phase, see below
        // also note that it's important to have the same number of args because see __invoke signature
        $this->stack->push([$this, null, null], $previousResult);
        $this->stack->push($s1, $previousResult);

        $this->firstPhase = false;

        return null; // always non-terminal
    }

    private function choiceIntermediate(NodeAdapterInterface $previousResult): ?NodeAdapterInterface
    {
        $res = $this->stack->getOriginalDatum();

        if ($this->failValue !== $previousResult) {
            $this->stack->pop(); // pop self; $s2 will be auto-popped
            $res = $previousResult; // pass $s1 result
        }

        return $res;
    }
}
