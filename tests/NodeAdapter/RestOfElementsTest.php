<?php

namespace JaneOlszewska\Tests\Itinerant\NodeAdapter;

use JaneOlszewska\Itinerant\NodeAdapter\RestOfElements;
use PHPUnit\Framework\TestCase;

class RestOfElementsTest extends TestCase
{
    public function testGetChildren()
    {
        $a = [1, 2, 3]; // node: 1, children: 2, 3

        $adapter = new RestOfElements($a);

        $children = $adapter->getChildren();
        // unwrap
        $children = array_map(function(RestOfElements $node) {
            return $node->getNode();
        }, iterator_to_array($children));

        $this->assertEquals([2, 3], $children);
    }

    public function testSetChildren()
    {
        $v1 = 4;
        $v2 = 5;

        $a = [1, 2, 3]; // node: 1, children: 2, 3

        $adapter = new RestOfElements($a);
        $adapter->setChildren([new RestOfElements($v1), new RestOfElements($v2)]);

        $this->assertEquals([1, 4, 5], $adapter->getNode());
    }


    public function testNoChildren()
    {
        $a = [1];
        $adapter = new RestOfElements($a);

        $this->assertFalse($adapter->getChildren()->valid());
    }
}
