<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class One
{
    private $firstPhase = true;

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
        NodeAdapterInterface $node,
        $childStrategy
    ) {
        $this->childStrategy = $childStrategy;
        $this->node = $node;
    }

    public function __invoke($previousResult)
    {
        $result = $this->firstPhase
            ? $this->one()
            : $this->oneIntermediate($previousResult);

        return $result;
    }

    private function one()
    {
        // if $d has no children: fail, strategy terminal independent of what $s1 actually is
        $res = Fail::fail();

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

    private function oneIntermediate(NodeAdapterInterface $previousResult)
    {
        $res = $previousResult;

        if (Fail::fail() === $previousResult) {
            // if the result of the last child resolution was fail, need to try with the next one (if exists)

            if ($this->unprocessed) { // fail, but there's more to process
                $child = array_shift($this->unprocessed);

                return [
                    [$this, null],
                    [$this->childStrategy, $child]
                ];
            }

            // else: well we processed everything and nothing succeeded so: FAIL ($res === $previousResult === FAIL)
        }

        return $res;
    }
}
