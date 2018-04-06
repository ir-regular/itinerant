<?php

namespace JaneOlszewska\Tests\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Instruction\StringDefinition;
use PHPUnit\Framework\TestCase;

class StringDefinitionTest extends TestCase
{
    /** @var resource */
    private $tryDefinition;

    /** @var resource */
    private $belowEqDefinition;

    protected function setUp()
    {
        $this->tryDefinition = $this->get_string_stream('try(s) = choice(s, id)');
        $this->belowEqDefinition = $this->get_string_stream('below_eq(s1, s2) = once_td(seq(s2, once_td(s1)))');
    }

    protected function tearDown()
    {
        fclose($this->tryDefinition);
        fclose($this->belowEqDefinition);
    }

    public function testExtractsStrategyKey()
    {
        $this->assertEquals('try', (new StringDefinition($this->tryDefinition))->getValue());
        $this->assertEquals('below_eq', (new StringDefinition($this->belowEqDefinition))->getValue());
    }

    public function testExtractsArgumentCount()
    {
        /** @var NodeAdapterInterface $declaration */
        $declaration = (new StringDefinition($this->tryDefinition))->getChildren()->current();
        // s
        $this->assertEquals(1, iterator_count($declaration->getChildren()));

        /** @var NodeAdapterInterface $declaration */
        $declaration = (new StringDefinition($this->belowEqDefinition))->getChildren()->current();
        // s1, s2
        $this->assertEquals(2, iterator_count($declaration->getChildren()));
    }

    public function testExtractsInstruction()
    {
        $children = (new StringDefinition($this->belowEqDefinition))->getChildren();
        $children->next(); // skip over declaration to instruction
        /** @var NodeAdapterInterface $instruction */
        $instruction = $children->current();

        $this->assertEquals(['once_td', ['seq', ['s2'], ['once_td', ['s1']]]], $instruction->getNode());
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
