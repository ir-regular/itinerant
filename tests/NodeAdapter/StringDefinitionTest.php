<?php

namespace JaneOlszewska\Tests\Itinerant\NodeAdapter;

use JaneOlszewska\Itinerant\NodeAdapter\StringDefinition;
use PHPUnit\Framework\TestCase;

class StringDefinitionTest extends TestCase
{
    private $tryDefinition;

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
        // @TODO: differentiate args from expansion
        // (I tried using Iterator and it breaks at recursion - expansion starts returning innermost child
        // ...and that's because I didn't do the proper depth-first tree walk I think :D)
        $arguments = (new StringDefinition($this->tryDefinition))->getChildren();
        // s, choice
        $this->assertEquals(2, iterator_count($arguments));

        $arguments = (new StringDefinition($this->belowEqDefinition))->getChildren();
        // s1, s2, once_td
        $this->assertEquals(3, iterator_count($arguments));
    }

    public function testRecursesIntoChildren()
    {
        $children = (new StringDefinition($this->belowEqDefinition))->getChildren();

        $s1 = $children->current();
        $this->assertEquals('s1', $s1->getValue());
        $children->next();

        $s2 = $children->current();
        $this->assertEquals('s2', $s2->getValue());
        $children->next();

        $oncetd = $children->current();
        $this->assertEquals('once_td', $oncetd->getValue());
        $children = $oncetd->getChildren();

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
