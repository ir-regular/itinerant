<?php

namespace IrRegular\Tests\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;
use IrRegular\Itinerant\NodeAdapter\Pair;
use IrRegular\Itinerant\NodeAdapter\Fail;
use IrRegular\Itinerant\Instruction\Seq;
use PHPUnit\Framework\TestCase;

class SeqTest extends TestCase
{
    private $initialInstruction = ['initial'];
    private $followupInstruction = ['followup'];

    /** @var NodeAdapterInterface */
    private $node;

    /** @var Seq */
    private $seq;

    protected function setUp()
    {
        $this->node = new Pair([1, [2, 3]]);
        $this->seq = new Seq($this->initialInstruction, $this->followupInstruction);
    }

    public function testExecutesFollowupWhenInitialSucceeded()
    {
        $continuation = $this->seq->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals([$this->initialInstruction, $this->node], $result);
        $this->assertTrue($continuation->valid());

        $result = $continuation->send($this->node);
        $this->assertEquals([$this->followupInstruction, $this->node], $result);
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

    public function testSkipsFollowupWhenInitialFailed()
    {
        $continuation = $this->seq->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals([$this->initialInstruction, $this->node], $result);
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
}
