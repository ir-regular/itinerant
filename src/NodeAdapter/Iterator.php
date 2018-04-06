<?php

namespace JaneOlszewska\Itinerant\NodeAdapter;

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
        return [$this->value, $this->iterator];
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
