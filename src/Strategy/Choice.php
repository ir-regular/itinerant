<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Choice
{
    /** @var array */
    private $initialStrategy;

    /** @var array */
    private $alternativeStrategy;

    public function __construct(
        array $initialStrategy,
        array $alternativeStrategy
    ) {
        $this->initialStrategy = $initialStrategy;
        $this->alternativeStrategy = $alternativeStrategy;
    }

    public function __invoke(NodeAdapterInterface $node)
    {
        $result = yield [$this->initialStrategy, $node];

        if (Fail::fail() === $result) {
            $result = yield [$this->alternativeStrategy, $node];
        }

        yield $result;
    }
}
