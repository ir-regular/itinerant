<?php

namespace IrRegular\Tests\Itinerant;

use IrRegular\Itinerant\Itinerant;
use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration test.
 */
class ItinerantTest extends TestCase
{
    public function testRegistersInstructionUsingPreviouslyRegisteredInstruction()
    {
        $itinerant = new Itinerant();
        $itinerant->register('first', ['id']);
        $itinerant->register('second', ['choice', 'fail', 'first']);

        $node = $this->createMock(NodeAdapterInterface::class);

        $this->assertEquals($node, $itinerant->apply('first', $node));
        $this->assertEquals($node, $itinerant->apply('second', $node));
    }
}
