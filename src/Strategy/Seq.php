<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\StrategyStack;

class Seq
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
            ? $this->seq($previousResult, $s1, $s2)
            : $this->seqIntermediate($previousResult, $s2);

        return $result;
    }

    private function seq(NodeAdapterInterface $previousResult, $s1, $s2)
    {
        $this->firstPhase = false;

        $this->stack->pop(); // remove self

        // re-push self, but next time will execute second phase, see below
        // also note that it's important to have the same number of args because see __invoke signature
        $this->stack->push([$this, null, $s2], $this->failValue);
        $this->stack->push($s1, $previousResult);

        return null; // always non-terminal
    }

    private function seqIntermediate(NodeAdapterInterface $previousResult, $s2): ?NodeAdapterInterface
    {
        $res = $previousResult;

        if ($this->failValue !== $previousResult) {
            $this->stack->pop();
            $this->stack->push($s2, $previousResult);
            $res = null;
        }

        return $res;
    }
}
