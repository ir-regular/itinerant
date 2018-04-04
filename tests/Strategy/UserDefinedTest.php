<?php

namespace JaneOlszewska\Tests\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use JaneOlszewska\Itinerant\Strategy\UserDefined;
use PHPUnit\Framework\TestCase;

class UserDefinedTest extends TestCase
{
    public function testResolvesKeyToContinuation()
    {
        $expansion = ['seq', ['0', '1']];
        $node = new SecondElement([1, [2, 3]]);
        $userDefined = new UserDefined($expansion, ['id', 'fail']);

        $continuation = $userDefined->apply($node);

        $result = $continuation->current();
        $this->assertEquals([['seq', ['id', 'fail']], $node], $result);

        // pretend $node is the result of applying the expansion to $node
        $result = $continuation->send($node);
        $this->assertEquals($node, $result); // ...and UserDefined resolves to that result
    }
}
