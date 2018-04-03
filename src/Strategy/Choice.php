<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Choice
{
    private $firstPhase = true;

    /**
     * @var NodeAdapterInterface
     */
    private $node;

    private $initialStrategy;

    private $alternativeStrategy;

    public function __construct(
        $initialStrategy,
        $alternativeStrategy,
        NodeAdapterInterface $node = null
    ) {
        if ($node) {
            $this->node = $node;
        }

        $this->initialStrategy = $initialStrategy;
        $this->alternativeStrategy = $alternativeStrategy;
    }

    public function __invoke(NodeAdapterInterface $previousResult)
    {
        $result = $this->firstPhase
            ? $this->choice($previousResult)
            : $this->choiceIntermediate($previousResult);

        return $result;
    }

    private function choice(NodeAdapterInterface $node)
    {
        if (!$this->node) {
            $this->node = $node;
        }

        $this->firstPhase = false;

        return [
            [$this, null],
            [$this->initialStrategy, $this->node]
        ];
    }

    private function choiceIntermediate(NodeAdapterInterface $previousResult)
    {
        if (Fail::fail() !== $previousResult) {
            return $previousResult; // pass $s1 result
        }

        return [
            [$this->alternativeStrategy, $this->node]
        ];
    }
}
