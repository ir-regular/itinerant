<?php

namespace JaneOlszewska\Tests\Itinerant;

use JaneOlszewska\Itinerant\Itinerant;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
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

    public function testRegistersInstructionsFromStream()
    {
        // let's pretend we have a file
        $string = "try(s) = choice(s, id)\nrepeat(s) = try(choice(s, repeat(s)))\n";
        $stream = fopen('data://text/plain,' . $string, 'r');

        $itinerant = new Itinerant();
        $itinerant->registerFromStream($stream);

        fclose($stream);

        $node = $this->createMock(NodeAdapterInterface::class);

        $this->assertEquals($node, $itinerant->apply(['try', 'id'], $node));
        $this->assertEquals($node, $itinerant->apply(['repeat', 'id'], $node));
    }
}
