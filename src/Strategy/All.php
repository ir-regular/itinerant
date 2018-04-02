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

    /**
     * @var NodeAdapterInterface
     */
    private $node;

    /**
     * @var NodeAdapterInterface[]
     */
    private $unprocessed;

    /**
     * @var NodeAdapterInterface[]
     */
    private $processed;

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

    public function __invoke(NodeAdapterInterface $previousResult): ?NodeAdapterInterface
    {
        $result = $this->firstPhase
            ? $this->all($this->node, $this->childStrategy)
            : $this->allIntermediate($previousResult, $this->childStrategy);

        return $result;
    }

    private function all(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
    {
        // if $d has no children: return $d, strategy terminal independent of what $s1 actually is
        $res = $this->node;

        $unprocessed = $this->node->getChildren();
        $this->unprocessed = iterator_to_array($unprocessed);
        $this->processed = [];

        if ($this->unprocessed) {
            $child = array_shift($this->unprocessed);

            $this->stack->pop();

            $this->stack->push([$this, $s1]);
            $this->stack->push($s1);
            $this->stack->push([null]); // only here to be immediately popped

            $this->firstPhase = false;

            $res = $child;
        }

        return $res;
    }

    private function allIntermediate(NodeAdapterInterface $previousResult, $s1): ?NodeAdapterInterface
    {
        $res = $previousResult;

        // if the result of the last child resolution wasn't fail, continue
        if ($this->failValue !== $previousResult) {
            $this->processed[] = $previousResult;

            if ($this->unprocessed) { // there's more to process
                $child = array_shift($this->unprocessed);

                $this->stack->pop();

                $this->stack->push([$this, $s1]);
                $this->stack->push($s1);
                $this->stack->push([null]); // only here to be popped
                $res = $child;
            } else {
                $this->node->setChildren($this->processed);
                $res = $this->node;
            }
        }

        return $res;
    }
}
