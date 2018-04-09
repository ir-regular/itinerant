<?php

namespace JaneOlszewska\Itinerant\NodeAdapter;

class RestOfElements implements NodeAdapterInterface
{
    /**
     * @var array
     */
    private $node;

    public function __construct($node)
    {
        if (!is_array($node) || is_callable($node)) {
            $node = [$node];
        }

        $this->node = $node;
    }

    public function getNode()
    {
        return $this->node;
    }

    public function getValue()
    {
        return $this->node[0];
    }

    public function getChildren(): \Iterator
    {
        foreach (array_slice($this->node, 1) as $child) {
            // wrap
            yield new RestOfElements($child);
        }
    }

    public function setChildren(array $children = []): void
    {
        // unwrap
        $children = array_map(function (NodeAdapterInterface $nodeAdapter) {
            return $nodeAdapter->getNode();
        }, $children);

        array_splice($this->node, 1, count($this->node), $children);
    }
}
