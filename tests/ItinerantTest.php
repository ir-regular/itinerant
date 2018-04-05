<?php

namespace JaneOlszewska\Tests\Itinerant;

use JaneOlszewska\Itinerant\Itinerant;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use PHPUnit\Framework\TestCase;

class ItinerantTest extends TestCase
{
    public function testRegistersStrategyUsingRegisteredStrategy()
    {
        $itinerant = new Itinerant();
        $itinerant->registerStrategy('first', ['id']);
        $itinerant->registerStrategy('second', ['choice', 'fail', 'first']);

        $node = $this->createMock(NodeAdapterInterface::class);

        $this->assertEquals($node, $itinerant->apply('first', $node));
        $this->assertEquals($node, $itinerant->apply('second', $node));
    }
}
