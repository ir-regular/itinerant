<?php

namespace JaneOlszewska\Tests\Itinerant\Config;

use JaneOlszewska\Itinerant\Config\Configurator;
use JaneOlszewska\Itinerant\Itinerant;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use PHPUnit\Framework\TestCase;

class ConfiguratorTest extends TestCase
{
    public function testRegistersInstructionsFromStream()
    {
        // let's pretend we have a file
        $string = "try(s) = choice(s, id)\nrepeat(s) = try(choice(s, repeat(s)))\n";
        $stream = fopen('data://text/plain,' . $string, 'r');

        $itinerant = new Itinerant();
        Configurator::registerFromStream($itinerant, $stream);

        fclose($stream);

        $node = $this->createMock(NodeAdapterInterface::class);

        $this->assertEquals($node, $itinerant->apply(['try', 'id'], $node));
        $this->assertEquals($node, $itinerant->apply(['repeat', 'id'], $node));
    }
}
