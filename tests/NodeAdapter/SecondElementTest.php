<?php

namespace JaneOlszewska\Tests\Itinerant\NodeAdapter;

use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use PHPUnit\Framework\TestCase;

class SecondElementTest extends TestCase
{
    public function testGetChildren()
    {
        $a = [1, [2, 3]];
        $adapter = new SecondElement($a);

        $children = $adapter->getChildren();
        // unwrap
        $children = array_map(function(SecondElement $node) {
            return $node->getNode();
        }, iterator_to_array($children));

        $this->assertEquals([2, 3], $children);
    }

    public function testSetChildren()
    {
        $v1 = 4;
        $v2 = 5;

        $a = [1, [2, 3]];
        $adapter = new SecondElement($a);

        $adapter->setChildren([new SecondElement($v1), new SecondElement($v2)]);
        $this->assertEquals([1, [4, 5]], $adapter->getNode());
    }
}
