<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Id
{
    /**
     * @var NodeAdapterInterface
     */
    private $node;

    public function __construct(NodeAdapterInterface $node = null)
    {
        if ($node) {
            $this->node = $node;
        }
    }

    public function __invoke(NodeAdapterInterface $node): NodeAdapterInterface
    {
        return $this->node ?: $node;
    }
}
