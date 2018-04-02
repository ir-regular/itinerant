<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\StrategyStack;

class One
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

    /**
     * @var NodeAdapterInterface[]
     */
    private $unprocessed;
    private $childStrategy;

    public function __construct(
        StrategyStack $stack,
        NodeAdapterInterface $failValue,
        NodeAdapterInterface $node,
        $childStrategy
    ) {
        $this->stack = $stack;
        $this->failValue = $failValue;
        $this->childStrategy = $childStrategy;
        $this->node = $node;
    }

    public function __invoke($previousResult): ?NodeAdapterInterface
    {
        $result = $this->firstPhase
            ? $this->one($this->node, $this->childStrategy)
            : $this->oneIntermediate($previousResult, $this->childStrategy);

        return $result;
    }

    private function one(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
    {
        // if $d has no children: fail, strategy terminal independent of what $s1 actually is
        $res = $this->failValue;

        $unprocessed = $this->node->getChildren();
        $this->unprocessed = iterator_to_array($unprocessed);
        $this->processed = [];

        if ($this->unprocessed) {
            $child = array_shift($this->unprocessed);
            $this->stack->pop();

            // not interested in preserving previously processed results: thus null
            $this->stack->push([$this, $s1]);
            $this->stack->push($s1);
            $this->stack->push([null]); // only here to be popped
            $res = $child;

            $this->firstPhase = false;
        }

        return $res;
    }

    private function oneIntermediate(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
    {
        $res = $previousResult;

        if ($this->failValue === $previousResult) {
            // if the result of the last child resolution was fail, need to try with the next one (if exists)

            if ($this->unprocessed) { // fail, but there's more to process
                $child = array_shift($this->unprocessed);
                $this->stack->pop();

                // not interested in previously processed results: thus null
                $this->stack->push([$this, $s1]);
                $this->stack->push($s1);
                $this->stack->push([null]); // only here to be popped
                $res = $child;
            }

            // else: well we processed everything and nothing succeeded so: FAIL ($res === $previousResult === FAIL)
        }

        return $res;
    }
}
