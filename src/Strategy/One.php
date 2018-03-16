<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\ChildHandler\ChildHandlerInterface;
use JaneOlszewska\Itinerant\StrategyStack;
use JaneOlszewska\Itinerant\TraversalStrategy;

class One
{
    private $firstPhase = true;

    /**
     * @var StrategyStack
     */
    private $stack;

    private $failValue;

    /**
     * @var ChildHandlerInterface
     */
    private $childHandler;

    public function __construct(StrategyStack $stack, $failValue, ChildHandlerInterface $childHandler)
    {
        $this->stack = $stack;
        $this->failValue = $failValue;
        $this->childHandler = $childHandler;
    }

    public function __invoke($previousResult, $s)
    {
        $result = $this->firstPhase
            ? $this->one($previousResult, $s)
            : $this->oneIntermediate($previousResult, $s);

        return $result;
    }

    private function one($previousResult, $s1)
    {
        // if $d has no children: fail, strategy terminal independent of what $s1 actually is
        $res = $this->failValue;
        $unprocessed = $this->childHandler->getChildren($previousResult);

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

    private function oneIntermediate($previousResult, $s1)
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
