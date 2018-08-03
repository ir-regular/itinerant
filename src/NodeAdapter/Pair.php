<?php

namespace IrRegular\Itinerant\NodeAdapter;

class Pair implements NodeAdapterInterface
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
        foreach ($this->node[1] ?? [] as $child) {
            yield new Pair($child);
        }
    }

    public function setChildren(array $children = []): void
    {
        // unwrap
        $children = array_map(function (NodeAdapterInterface $nodeAdapter) {
            return $nodeAdapter->getNode();
        }, $children);

        if ($children) {
            $this->node[1] = $children;
        }
    }
}
