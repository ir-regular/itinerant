<?php

namespace IrRegular\Tests\Itinerant\NodeAdapter;

use IrRegular\Itinerant\NodeAdapter\Accessor;
use PHPUnit\Framework\TestCase;

class AccessorTest extends TestCase
{
    public function testGetChildren()
    {
        $a = $this->getDatum(1, [2, 3]);
        $adapter = new Accessor($a);

        // check that adapter can access the correct children

        $adapterChildren = $adapter->getChildren();
        // unwrap
        $adapterChildren = array_map(function(Accessor $node) {
            return $node->getNode();
        }, iterator_to_array($adapterChildren));

        $this->assertEquals(2, $adapterChildren[0]->v);
        $this->assertEquals(3, $adapterChildren[1]->v);
    }

    public function testSetChildren()
    {
        $a = $this->getDatum(1, [2, 3]);
        $adapter = new Accessor($a);

        $children = [];
        foreach ($this->wrapChildren([4, 5]) as $child) {
            $children[] = new Accessor($child);
        }

        // check that when adapter updates the object with the new children

        $adapter->setChildren($children);

        $children = $a->getChildren();

        $this->assertEquals(4, $children[0]->v);
        $this->assertEquals(5, $children[1]->v);
    }

    public function testNoChildren()
    {
        $a = $this->getDatum(1, []);
        $adapter = new Accessor($a);

        $this->assertFalse($adapter->getChildren()->valid());
    }

    /**
     * @param array $children
     * @return object[]
     */
    private function wrapChildren(array $children)
    {
        return array_map(function ($child) {
            return $this->getDatum($child, []); // wrap
        }, $children);
    }

    /**
     * @param mixed $v
     * @param array $children
     * @return object
     */
    private function getDatum($v, array $children)
    {
        $children = $this->wrapChildren($children);

        return new class($v, $children)
        {
            public $v;
            /** @var \ArrayObject */
            private $c;

            public function __construct($v, $children)
            {
                $this->v = $v;
                $this->c = new \ArrayObject();

                $this->setChildren($children);
            }

            public function getValue()
            {
                return $this->v;
            }

            public function getChildren(): ?array
            {
                return $this->c->getArrayCopy();
            }

            public function setChildren(array $children = []): void
            {
                $this->c->exchangeArray($children);
            }
        };
    }
}
