<?php

namespace IrRegular\Itinerant\NodeAdapter;

/**
 * Utility node adapter: a wrapper around an iterator.
 *
 * The iterator should returns objects already wrapped in a NodeAdapterInterface implementation.
 */
class Iterator implements NodeAdapterInterface
{
    private $value;

    /**
     * @var \Iterator
     */
    private $iterator;

    public function __construct($value, \Iterator $iterator)
    {
        $this->value = $value;
        $this->iterator = $iterator;
    }

    public function getNode()
    {
        $children = [];

        /** @var NodeAdapterInterface $child */
        foreach ($this->iterator as $child) {
            $children[] = $child->getNode();
        }

        return [$this->value, $children];
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getChildren(): \Iterator
    {
        yield from $this->iterator;
    }

    public function setChildren(array $children = []): void
    {
        // can't
    }
}
