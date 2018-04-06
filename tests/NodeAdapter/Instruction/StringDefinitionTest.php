<?php

namespace JaneOlszewska\Tests\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Instruction\StringDefinition;
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

        $this->tryDefinition = new StringDefinition($this->tryDefinitionStream, ['choice', 'id']);
        $this->belowEqDefinition = new StringDefinition($this->belowEqDefinitionStream, ['once_td', 'seq']);
    }

    protected function tearDown()
    {
        fclose($this->tryDefinitionStream);
        fclose($this->belowEqDefinitionStream);
    }

    public function testExtractsStrategyKey()
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

    public function testExtractsInstruction()
    {
        $children = $this->belowEqDefinition->getChildren();
        $children->next(); // skip over declaration to instruction
        /** @var NodeAdapterInterface $instruction */
        $instruction = $children->current();

        $this->assertEquals(['once_td', ['seq', ['s2'], ['once_td', ['s1']]]], $instruction->getNode());
    }

    public function testThrowsIfUnknownSymbolEncountered()
    {
        $stream = $this->get_string_stream('below_eq(s1, s2) = once_td(seq(s3, once_td(s1)))');

        try {
            // will not understand 's3', since it's not one of the existing parameters
            (new StringDefinition($stream, ['once_td', 'seq']))->getNode();
            $this->fail('Did not fail on unrecognised symbol');

        } catch (\UnexpectedValueException $e) {
            $this->assertEquals('Unknown symbol: s3', $e->getMessage());

        } finally {
            fclose($stream);
        }
    }

    public function testThrowsWhenDefinitionBodyMissing()
    {
        $stream = $this->get_string_stream('below_eq(s1, s2)');

        try {
            // will not understand 's3', since it's not one of the existing parameters
            (new StringDefinition($stream))->getNode();
            $this->fail('Did not fail due to missing body');

        } catch (\UnderflowException $e) {
            $this->assertEquals('Definition below_eq incomplete: body missing', $e->getMessage());

        } finally {
            fclose($stream);
        }
    }

    public function testMultipleDefinitionsLoadFromSameStream()
    {
        $twoDefinitions = "try(s) = choice(s, id)\nbelow_eq(s1, s2) = once_td(seq(s2, once_td(s1)))";
        $stream = $this->get_string_stream($twoDefinitions);

        $knownSymbols = ['choice', 'id', 'seq', 'once_td'];

        $this->assertEquals(
            ['try', ['choice', ['s'], ['id']]],
            (new StringDefinition($stream, $knownSymbols))->getNode()
        );
        $this->assertEquals(
            ['below_eq', ['once_td', ['seq', ['s2'], ['once_td', ['s1']]]]],
            (new StringDefinition($stream, $knownSymbols))->getNode()
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
