<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class One
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
