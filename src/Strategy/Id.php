<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Id
{
    public function __invoke(NodeAdapterInterface $node)
    {
        yield $node;
    }
}
