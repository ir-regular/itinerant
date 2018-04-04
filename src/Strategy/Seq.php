<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Seq
{
    /** @var array */
    private $initialStrategy;

    /** @var array */
    private $followupStrategy;

    public function __construct(
        array $initialStrategy,
        array $followupStrategy
    ) {
        $this->initialStrategy = $initialStrategy;
        $this->followupStrategy = $followupStrategy;
    }

    public function __invoke(NodeAdapterInterface $node): \Generator
    {
        $result = yield [$this->initialStrategy, $node];

        if (Fail::fail() !== $result) {
            $result = yield [$this->followupStrategy, $result];
        }

        yield $result;
    }
}
