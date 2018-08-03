<?php

namespace IrRegular\Tests\Itinerant\Config;

use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;
use IrRegular\Itinerant\Config\StringDefinition;
use PHPUnit\Framework\TestCase;

class StringDefinitionTest extends TestCase
{
    /** @var resource */
    private $tryDefinitionStream;

    /** @var resource */
    private $belowEqDefinitionStream;

    /** @var StringDefinition */
    private $tryDefinition;

    /** @var StringDefinition */
    private $belowEqDefinition;

    protected function setUp()
    {
        $this->tryDefinitionStream = $this->get_string_stream('try(s) = choice(s, id)');
        $this->belowEqDefinitionStream = $this->get_string_stream('below_eq(s1, s2) = once_td(seq(s2, once_td(s1)))');

        $this->tryDefinition = new StringDefinition($this->tryDefinitionStream);
        $this->belowEqDefinition = new StringDefinition($this->belowEqDefinitionStream);
    }

    protected function tearDown()
    {
        fclose($this->tryDefinitionStream);
        fclose($this->belowEqDefinitionStream);
    }

    public function testExtractsInstructionName()
    {
        $this->assertEquals('try', $this->tryDefinition->getValue());
        $this->assertEquals('below_eq', $this->belowEqDefinition->getValue());
    }

    public function testExtractsArguments()
    {
        /** @var NodeAdapterInterface $declaration */
        $declaration = $this->tryDefinition->getChildren()->current();
        $this->assertEquals(['try', ['s']], $declaration->getNode());

        /** @var NodeAdapterInterface $declaration */
        $declaration = $this->belowEqDefinition->getChildren()->current();
        $this->assertEquals(['below_eq', ['s1'], ['s2']], $declaration->getNode());
    }

    public function testExtractsDefinition()
    {
        $children = $this->belowEqDefinition->getChildren();
        $children->next(); // skip over declaration to instruction
        /** @var NodeAdapterInterface $instruction */
        $instruction = $children->current();

        $this->assertEquals(['once_td', ['seq', '1', ['once_td', '0']]], $instruction->getNode());
    }

    public function testThrowsWhenDefinitionMissing()
    {
        $stream = $this->get_string_stream('below_eq(s1, s2)');

        try {
            // will not understand 's3', since it's not one of the existing parameters
            (new StringDefinition($stream))->getNode();
            $this->fail('Did not fail due to missing body');

        } catch (\UnderflowException $e) {
            $this->assertEquals('below_eq incomplete: definition missing', $e->getMessage());

        } finally {
            fclose($stream);
        }
    }

    public function testMultipleDefinitionsLoadFromSameStream()
    {
        $twoDefinitions = "try(s) = choice(s, id)\nbelow_eq(s1, s2) = once_td(seq(s2, once_td(s1)))";
        $stream = $this->get_string_stream($twoDefinitions);

        $nodes = [];

        while (($peeked = fgetc($stream)) !== false) {
            $nodes[] = (new StringDefinition($stream, $peeked))->getNode();
        }

        $this->assertEquals(
            [
                ['try', ['choice', '0', ['id']]],
                ['below_eq', ['once_td', ['seq', '1', ['once_td', '0']]]],
            ],
            $nodes
        );

        fclose($stream);
    }

    /**
     * @param string $string
     * @return resource
     */
    private function get_string_stream(string $string)
    {
        return fopen('data://text/plain,' . $string, 'r');
    }
}
