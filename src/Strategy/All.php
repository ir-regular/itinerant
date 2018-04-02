<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class All
{
    private $firstPhase = true;

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
        NodeAdapterInterface $failValue,
        NodeAdapterInterface $node,
        $childStrategy
    ) {
        $this->failValue = $failValue;
        $this->childStrategy = $childStrategy;
        $this->node = $node;
    }

    public function __invoke(NodeAdapterInterface $previousResult)
    {
        $result = $this->firstPhase
            ? $this->all()
            : $this->allIntermediate($previousResult);

        return $result;
    }

    private function all()
    {
        // if $d has no children: return $d, strategy terminal independent of what $s1 actually is
        $res = $this->node;

        $unprocessed = $this->node->getChildren();
        $this->unprocessed = iterator_to_array($unprocessed);
        $this->processed = [];

        if ($this->unprocessed) {
            $this->firstPhase = false;
            $child = array_shift($this->unprocessed);

            return [
                [$this, null],
                [$this->childStrategy, $child]
            ];
        }

        return $res;
    }

    private function allIntermediate(NodeAdapterInterface $previousResult)
    {
        $res = $previousResult;

        // if the result of the last child resolution wasn't fail, continue
        if ($this->failValue !== $previousResult) {
            $this->processed[] = $previousResult;

            if ($this->unprocessed) { // there's more to process
                $child = array_shift($this->unprocessed);

                return [
                    [$this, null],
                    [$this->childStrategy, $child]
                ];
            } else {
                $this->node->setChildren($this->processed);
                $res = $this->node;
            }
        }

        return $res;
    }
}
