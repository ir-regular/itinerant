<?php

namespace JaneOlszewska\Tests\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use JaneOlszewska\Itinerant\Strategy\Fail;
use PHPUnit\Framework\TestCase;

/**
 * For completeness, but I'm considering getting rid of Fail class
 */
class FailTest extends TestCase
{
    public function testReturnsFail()
    {
        $node = new SecondElement([1, [2, 3]]);
        $fail = new Fail();

        /** @var \Generator $continuation */
        $continuation = $fail($node);
        $this->assertInstanceOf(\Generator::class, $continuation);

        $result = $continuation->current();
        $this->assertEquals(Fail::fail(), $result);
    }
}
