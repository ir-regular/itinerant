<?php

namespace IrRegular\Tests\Itinerant\Config;

use IrRegular\Itinerant\Config\Configurator;
use IrRegular\Itinerant\Itinerant;
use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;
use PHPUnit\Framework\TestCase;

class ConfiguratorTest extends TestCase
{
    public function testRegistersInstructionsFromStream()
    {
        // let's pretend we have a file
        $string = "try(s) = choice(s, id)\nrepeat(s) = try(choice(s, repeat(s)))\n";
        $stream = fopen('data://text/plain,' . $string, 'r');

        $itinerant = new Itinerant();

        try {
            Configurator::registerFromStream($itinerant, $stream);
        } finally {
            fclose($stream);
        }

        $node = $this->createMock(NodeAdapterInterface::class);

        $this->assertEquals($node, $itinerant->apply(['try', 'id'], $node));
        $this->assertEquals($node, $itinerant->apply(['repeat', 'id'], $node));
    }

    public function testRegistersInstructionsFromFile()
    {
        $filepath = getcwd() . '/resources/common.itinerant';
        $itinerant = new Itinerant();
        Configurator::registerFromFile($itinerant, $filepath);

        $node = $this->createMock(NodeAdapterInterface::class);

        $this->assertEquals($node, $itinerant->apply(['try', 'id'], $node));
//        $this->assertEquals($node, $itinerant->apply(['repeat', 'id'], $node));
    }
}
