<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class All
{
    /** @var array */
    private $childStrategy;

    public function __construct(
        array $childStrategy
    ) {
        $this->childStrategy = $childStrategy;
    }

    public function __invoke(NodeAdapterInterface $node)
    {
        // if $node has no children: return $node by default
        $result = $node;

        $unprocessed = $node->getChildren();
        $unprocessed = iterator_to_array($unprocessed);
        $processed = [];

        if ($unprocessed) {
            while ($child = array_shift($unprocessed)) {
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
