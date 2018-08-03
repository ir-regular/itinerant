<?php

namespace IrRegular\Itinerant\NodeAdapter;

class Leaf implements NodeAdapterInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getNode()
    {
        return $this->value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getChildren(): \Iterator
    {
        yield from []; // no children
    }

    public function setChildren(array $children = []): void
    {
        throw new \RuntimeException('Cannot set children on a leaf node');
    }
}
