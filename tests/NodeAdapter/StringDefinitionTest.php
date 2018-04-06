<?php

namespace JaneOlszewska\Tests\Itinerant\NodeAdapter;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\StringDefinition;
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

    public function testExtractsStringDefinitionKey()
    {
        $this->assertEquals('try', (new StringDefinition($this->tryDefinition))->getValue());
        $this->assertEquals('below_eq', (new StringDefinition($this->belowEqDefinition))->getValue());
    }

    public function testExtractsArguments()
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

    public function testRecursesIntoChildren()
    {
        $children = (new StringDefinition($this->belowEqDefinition))->getChildren();

        // check arguments

        $arguments = $children->current()->getChildren();

        $s1 = $arguments->current();
        $this->assertEquals('s1', $s1->getValue());
        $arguments->next();

        $s2 = $arguments->current();
        $this->assertEquals('s2', $s2->getValue());

        // check instruction

        $children->next();
        $instruction = $children->current();
        $this->assertEquals('once_td', $instruction->getValue());
        $children = $instruction->getChildren();

        $seq = $children->current();
        $this->assertEquals('seq', $seq->getValue());
        $children = $seq->getChildren();

        $s2 = $children->current();
        $this->assertEquals('s2', $s2->getValue());
        $children->next();

        $oncetd = $children->current();
        $this->assertEquals('once_td', $oncetd->getValue());
        $children = $oncetd->getChildren();

        $s1 = $children->current();
        $this->assertEquals('s1', $s1->getValue());
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
