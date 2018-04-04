<?php

namespace JaneOlszewska\Tests\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use JaneOlszewska\Itinerant\Strategy\Id;
use PHPStan\Testing\TestCase;

/**
 * For completeness, but I'm considering getting rid of Id class
 */
class IdTest extends TestCase
{
    public function testReturnsSameNode()
    {
        $node = new SecondElement([1, [2, 3]]);
        $id = new Id();

        /** @var \Generator $continuation */
        $continuation = $id($node);
        $this->assertInstanceOf(\Generator::class, $continuation);

        $result = $continuation->current();
        $this->assertEquals($node, $result);
    }
}
