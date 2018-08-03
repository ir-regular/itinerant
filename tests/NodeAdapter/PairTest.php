<?php

namespace IrRegular\Tests\Itinerant\NodeAdapter;

use IrRegular\Itinerant\NodeAdapter\Pair;
use PHPUnit\Framework\TestCase;

class PairTest extends TestCase
{
    public function testGetChildren()
    {
        $a = [1, [2, 3]];
        $adapter = new Pair($a);

        $children = $adapter->getChildren();
        // unwrap
        $children = array_map(function(Pair $node) {
            return $node->getNode();
        }, iterator_to_array($children));

        $this->assertEquals([[2], [3]], $children);
    }

    public function testSetChildren()
    {
        $v1 = 4;
        $v2 = 5;

        $a = [1, [2, 3]];
        $adapter = new Pair($a);

        $adapter->setChildren([new Pair($v1), new Pair($v2)]);
        $this->assertEquals([1, [[4], [5]]], $adapter->getNode());
    }

    public function testNoChildren()
    {
        $a = [1];
        $adapter = new Pair($a);

        $this->assertFalse($adapter->getChildren()->valid());
    }
}
