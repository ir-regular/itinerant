<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\StrategyStack;
use JaneOlszewska\Itinerant\TraversalStrategy;

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

    public function __construct(StrategyStack $stack, NodeAdapterInterface $failValue)
    {
        $this->stack = $stack;
        $this->failValue = $failValue;
    }

    public function __invoke($previousResult, $s): ?NodeAdapterInterface
    {
        $result = $this->firstPhase
            ? $this->one($previousResult, $s)
            : $this->oneIntermediate($previousResult, $s);

        return $result;
    }

    private function one(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
    {
        // if $d has no children: fail, strategy terminal independent of what $s1 actually is
        $res = $this->failValue;
        $unprocessed = $previousResult->getChildren();
        // @TODO: in the next step, modify how this is stored on the stack
        $unprocessed = iterator_to_array($unprocessed);

        if ($unprocessed) {
            $this->stack->pop();

            // not interested in previously processed results: thus null
            $this->stack->push([$this, $s1], null, $unprocessed, null);
            $this->stack->push($s1, $unprocessed[0]);
            $this->stack->push([null]); // only here to be popped
            $res = $unprocessed[0];

            $this->firstPhase = false;
        }

        return $res;
    }

    private function oneIntermediate(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
    {
        $res = $previousResult;

        if ($this->failValue === $previousResult) {
            // if the result of the last child resolution was fail, need to try with the next one (if exists)

            $unprocessed = $this->stack->getUnprocessedChildren();
            array_shift($unprocessed);

            if ($unprocessed) { // fail, but there's more to process
                $this->stack->pop();

                // not interested in previously processed results: thus null
                $this->stack->push([$this, $s1], null, $unprocessed, null);
                $this->stack->push($s1, $unprocessed[0]);
                $this->stack->push([null]); // only here to be popped
                $res = $unprocessed[0];
            }

            // else: well we processed everything and nothing succeeded so: FAIL ($res === $previousResult === FAIL)
        }

        return $res;
    }
}
