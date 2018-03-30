<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\StrategyStack;

class All
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

    public function __invoke(NodeAdapterInterface $previousResult, $s): ?NodeAdapterInterface
    {
        $result = $this->firstPhase
            ? $this->all($previousResult, $s)
            : $this->allIntermediate($previousResult, $s);

        return $result;
    }

    private function all(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
    {
        // if $d has no children: return $d, strategy terminal independent of what $s1 actually is
        $res = $previousResult;
        $unprocessed = $previousResult->getChildren();
        // @TODO: in the next step, modify how this is stored on the stack
        $unprocessed = iterator_to_array($unprocessed);

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

    private function allIntermediate(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
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
                $originalResult->setChildren($processed);
                $res = $originalResult;
            }
        }

        return $res;
    }
}
