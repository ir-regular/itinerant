<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Id
{
    /**
     * @var NodeAdapterInterface
     */
    private $node;

    public function __construct(NodeAdapterInterface $node)
    {
        $this->node = $node;
    }

    public function __invoke(): NodeAdapterInterface
    {
        return $this->node;
    }
}
