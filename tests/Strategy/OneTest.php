<?php

namespace JaneOlszewska\Tests\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use JaneOlszewska\Itinerant\Strategy\Fail;
use JaneOlszewska\Itinerant\Strategy\One;
use PHPUnit\Framework\TestCase;

class OneTest extends TestCase
{
    private $childInstruction = ['resolve-child'];
    private $node;
    private $one;

    protected function setUp()
    {
        $this->node = new SecondElement([1, [2, 3]]);
        $this->one = new One($this->childInstruction);
    }

    public function testFailsIfAllChildrenFailed()
    {
        /** @var \Generator $continuation */
        $continuation = ($this->one)($this->node);
        $this->assertInstanceOf(\Generator::class, $continuation);

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
        $success = new SecondElement([2, [99]]);

        /** @var \Generator $continuation */
        $continuation = ($this->one)($this->node);
        $this->assertInstanceOf(\Generator::class, $continuation);

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
        $node = new SecondElement([2]);

        /** @var \Generator $continuation */
        $continuation = ($this->one)($node);

        $this->assertInstanceOf(\Generator::class, $continuation);
        $result = $continuation->current();
        $this->assertEquals(Fail::fail(), $result);
    }
}
