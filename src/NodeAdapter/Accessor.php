<?php

namespace IrRegular\Itinerant\NodeAdapter;

class Accessor implements NodeAdapterInterface
{
    /**
     * @var object
     */
    private $node;

    /**
     * @var string
     */
    private $getValue;

    /**
     * @var string
     */
    private $getChildren;

    /**
     * @var string
     */
    private $setChildren;

    public function __construct(
        $node,
        string $getValue = 'getValue',
        string $getChildren = 'getChildren',
        string $setChildren = 'setChildren'
    ) {
        if (!method_exists($node, $getValue)
            || !method_exists($node, $getChildren)
            || !method_exists($node, $setChildren)
        ) {
            throw new \InvalidArgumentException(
                "Wrapped node must provide methods $getValue, $getChildren, $setChildren"
            );
        }

        $this->node = clone $node;
        $this->getValue = $getValue;
        $this->getChildren = $getChildren;
        $this->setChildren = $setChildren;
    }

    public function getNode()
    {
        return $this->node;
    }

    public function getValue()
    {
        return $this->node->{$this->getValue}();
    }

    public function getChildren(): \Iterator
    {
        foreach ($this->node->{$this->getChildren}() ?? [] as $child) {
            // wrap
            yield new Accessor($child);
        }
    }

    public function setChildren(array $children = []): void
    {
        // unwrap
        $children = array_map(function (NodeAdapterInterface $nodeAdapter) {
            return $nodeAdapter->getNode();
        }, $children);

        $this->node->{$this->setChildren}($children);
    }
}
