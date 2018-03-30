<?php

namespace JaneOlszewska\Itinerant\NodeAdapter;

class RestOfElements implements NodeAdapterInterface
{
    /**
     * @var array
     */
    private $node;

    public function __construct(&$node)
    {
        $this->node = $node;
    }

    public function &getNode()
    {
        return $this->node;
    }

    public function getValue()
    {
        return is_array($this->node) ? $this->node[0] : $this->node;
    }

    public function getChildren(): \Iterator
    {
        if (is_array($this->node)) {
            foreach (array_slice($this->node, 1) as $child) {
                // wrap
                yield new RestOfElements($child);
            }
        }
    }

    public function setChildren(array $children = []): void
    {
        if (!is_array($this->node)) {
            // yeah... let's hope this works as intended when gathering results
            // might need to do something clever with references in getChildren wrt wrapping values
            $this->node = [$this->node];
        }

        // unwrap
        $children = array_map(function (RestOfElements $nodeAdapter) {
            return $nodeAdapter->getNode();
        }, $children);

        array_splice($this->node, 1, count($this->node), $children);
    }
}
