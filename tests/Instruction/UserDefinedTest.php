<?php

namespace IrRegular\Tests\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\Pair;
use IrRegular\Itinerant\Instruction\UserDefined;
use PHPUnit\Framework\TestCase;

class UserDefinedTest extends TestCase
{
    public function testResolvesKeyToContinuation()
    {
        $instruction = ['seq', ['0', '1']];
        $node = new Pair([1, [2, 3]]);
        $userDefined = new UserDefined($instruction, ['id', 'fail']);

        $continuation = $userDefined->apply($node);

        $result = $continuation->current();
        $this->assertEquals([['seq', ['id', 'fail']], $node], $result);

        // pretend $node is the result of applying the instruction to $node
        $result = $continuation->send($node);
        $this->assertEquals($node, $result); // ...and UserDefined resolves to that result
    }
}
