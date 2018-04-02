<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Seq
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
    private $initialStrategy;
    private $followupStrategy;

    public function __construct(
        NodeAdapterInterface $failValue,
        NodeAdapterInterface $node,
        $initialStrategy,
        $followupStrategy
    ) {
        $this->failValue = $failValue;
        $this->node = $node;
        $this->initialStrategy = $initialStrategy;
        $this->followupStrategy = $followupStrategy;
    }

    public function __invoke(NodeAdapterInterface $previousResult)
    {
        $result = $this->firstPhase
            ? $this->seq()
            : $this->seqIntermediate($previousResult);

        return $result;
    }

    private function seq()
    {
        $this->firstPhase = false;

        return [
            [$this, null],
            [$this->initialStrategy, $this->node]
        ];
    }

    private function seqIntermediate(NodeAdapterInterface $previousResult)
    {
        $res = $previousResult;

        if ($this->failValue !== $previousResult) {
            return [
                // $this->node could have been transformed by $this->initialStrategy into $previousResult
                [$this->followupStrategy, $previousResult]
            ];
        }

        return $res;
    }
}
