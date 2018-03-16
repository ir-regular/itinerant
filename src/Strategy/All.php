<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\ChildHandler\ChildHandlerInterface;
use JaneOlszewska\Itinerant\StrategyStack;
use JaneOlszewska\Itinerant\TraversalStrategy;

class All
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
            ? $this->all($previousResult, $s)
            : $this->allIntermediate($previousResult, $s);

        return $result;
    }

    private function all($previousResult, $s1)
    {
        // if $d has no children: return $d, strategy terminal independent of what $s1 actually is
        $res = $previousResult;
        $unprocessed = $this->childHandler->getChildren($previousResult);

        if ($unprocessed) {
            $this->stack->pop();

            $this->stack->push([$this, $s1], $previousResult, $unprocessed, []);
            $this->stack->push($s1, $unprocessed[0]);
            $this->stack->push([null]); // only here to be immediately popped
            $res = $unprocessed[0];

            $this->firstPhase = false;
        }

        return $res;
    }

    private function allIntermediate($previousResult, $s1)
    {
        $res = $previousResult;

        if ($this->failValue !== $previousResult) {
            $originalResult = $this->stack->getOriginalDatum();

            // if the result of the last child resolution wasn't fail, continue
            $unprocessed = $this->stack->getUnprocessedChildren();
            array_shift($unprocessed);

            $processed = $this->stack->getProcessedChildren();
            $processed[] = $previousResult;

            if ($unprocessed) { // there's more to process
                $this->stack->pop();

                $this->stack->push([$this, $s1], $originalResult, $unprocessed, $processed);
                $this->stack->push($s1, $unprocessed[0]);
                $this->stack->push([null]); // only here to be popped
                $res = $unprocessed[0];
            } else {
                $this->childHandler->setChildren($originalResult, $processed);
                $res = $originalResult;
            }
        }

        return $res;
    }
}
