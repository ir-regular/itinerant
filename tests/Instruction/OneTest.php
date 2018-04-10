<?php

namespace JaneOlszewska\Tests\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Pair;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\Instruction\One;
use PHPUnit\Framework\TestCase;

class OneTest extends TestCase
{
    private $childInstruction = ['resolve-child'];

    /** @var NodeAdapterInterface */
    private $node;

    /** @var One */
    private $one;

    protected function setUp()
    {
        $this->node = new Pair([1, [2, 3]]);
        $this->one = new One($this->childInstruction);
    }

    public function testFailsIfAllChildrenFailed()
    {
        $continuation = $this->one->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals($this->childInstruction, $result[0]);
        $this->assertEquals(2, $result[1]->getValue());
        $this->assertTrue($continuation->valid());

        $result = $continuation->send(Fail::fail());
        $this->assertEquals($this->childInstruction, $result[0]);
        $this->assertEquals(3, $result[1]->getValue());
        $this->assertTrue($continuation->valid());

        $result = $continuation->send(Fail::fail());
        $this->assertEquals(Fail::fail(), $result);
        $this->assertTrue($continuation->valid());

        // for illustration purposes only:
        // sadly, we can't tell just by calling valid() that we won't get any more values

        $result = $continuation->send(1);
        $this->assertNull($result);
        $this->assertFalse($continuation->valid());
    }

    public function testReturnsChildThatSucceeded()
    {
        $success = new Pair([2, [99]]);

        $continuation = $this->one->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals($this->childInstruction, $result[0]);
        $this->assertEquals(2, $result[1]->getValue());
        $this->assertTrue($continuation->valid());

        $result = $continuation->send($success);
        $this->assertEquals($success, $result);
        $this->assertTrue($continuation->valid());

        // for illustration purposes only:
        // sadly, we can't tell just by calling valid() that we won't get any more values

        $result = $continuation->send(1);
        $this->assertNull($result);
        $this->assertFalse($continuation->valid());
    }

    public function testFailsIfNoChildren()
    {
        $node = new Pair([2]);

        $continuation = $this->one->apply($node);

        $result = $continuation->current();
        $this->assertEquals(Fail::fail(), $result);
    }
}
