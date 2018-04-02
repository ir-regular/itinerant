<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Choice
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

    private $alternativeStrategy;

    public function __construct(
        NodeAdapterInterface $failValue,
        NodeAdapterInterface $node,
        $initialStrategy,
        $alternativeStrategy
    ) {
        $this->failValue = $failValue;
        $this->node = $node;
        $this->initialStrategy = $initialStrategy;
        $this->alternativeStrategy = $alternativeStrategy;
    }

    public function __invoke(NodeAdapterInterface $previousResult)
    {
        $result = $this->firstPhase
            ? $this->choice()
            : $this->choiceIntermediate($previousResult);

        return $result;
    }

    private function choice()
    {
        $this->firstPhase = false;

        return [
            [$this, null],
            [$this->initialStrategy, $this->node]
        ];
    }

    private function choiceIntermediate(NodeAdapterInterface $previousResult)
    {
        if ($this->failValue !== $previousResult) {
            return $previousResult; // pass $s1 result
        }

        return [
            [$this->alternativeStrategy, $this->node]
        ];
    }
}
