<?php

namespace JaneOlszewska\Tests\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Pair;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\Instruction\Choice;
use PHPUnit\Framework\TestCase;

class ChoiceTest extends TestCase
{
    private $initialExpression = ['initial'];
    private $alternativeExpression = ['alternative'];

    /** @var NodeAdapterInterface */
    private $node;

    /** @var Choice */
    private $choice;

    protected function setUp()
    {
        $this->node = new Pair([1, [2, 3]]);
        $this->choice = new Choice($this->initialExpression, $this->alternativeExpression);
    }

    public function testExecutesAlternativeWhenInitialExpressionFails()
    {
        $continuation = $this->choice->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals([$this->initialExpression, $this->node], $result);
        $this->assertTrue($continuation->valid());

        $result = $continuation->send(Fail::fail());
        $this->assertEquals([$this->alternativeExpression, $this->node], $result);
        $this->assertTrue($continuation->valid());

        $result = $continuation->send($this->node);
        $this->assertEquals($this->node, $result);
        $this->assertTrue($continuation->valid());

        // for illustration purposes only:
        // sadly, we can't tell just by calling valid() that we won't get any more values

        $result = $continuation->send(1);
        $this->assertNull($result);
        $this->assertFalse($continuation->valid());
    }

    public function testSkipsAlternativeWhenInitialExpressionSucceeds()
    {
        $continuation = $this->choice->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals([$this->initialExpression, $this->node], $result);
        $this->assertTrue($continuation->valid());

        $result = $continuation->send($this->node);
        $this->assertEquals($this->node, $result);
        $this->assertTrue($continuation->valid());

        // for illustration purposes only:
        // sadly, we can't tell just by calling valid() that we won't get any more values

        $result = $continuation->send(1);
        $this->assertNull($result);
        $this->assertFalse($continuation->valid());
    }
}
