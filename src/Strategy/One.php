<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class One implements StrategyInterface
{
    /** @var array */
    private $childStrategy;

    public function __construct(
        array $childStrategy
    ) {
        $this->childStrategy = $childStrategy;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        // if $node has no children: fail by default
        $result = Fail::fail();

        $unprocessed = $node->getChildren();

        if ($unprocessed->valid()) {
            foreach ($unprocessed as $child) {
                $result = yield [$this->childStrategy, $child];

                if (Fail::fail() !== $result) {
                    break;
                }
            }
        }

        yield $result;
    }
}
