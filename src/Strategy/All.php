<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class All implements StrategyInterface
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
        // if $node has no children: return $node by default
        $result = $node;

        $unprocessed = $node->getChildren();
        $processed = [];

        if ($unprocessed->valid()) {
            foreach ($unprocessed as $child) {
                $result = yield [$this->childStrategy, $child];

                if (Fail::fail() === $result) {
                    break;
                }

                $processed[] = $result;
            }

            if (Fail::fail() !== $result) {
                $node->setChildren($processed);
                $result = $node;
            }
        }

        yield $result;
    }
}
