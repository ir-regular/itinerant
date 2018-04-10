<?php

namespace JaneOlszewska\Tests\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\Instruction\ExpressionResolver;
use JaneOlszewska\Itinerant\Instruction\UserDefined;
use PHPUnit\Framework\TestCase;

class ExpressionResolverTest extends TestCase
{
    /** @var ExpressionResolver */
    private $resolver;

    protected function setUp()
    {
        parent::setUp();

        $this->resolver = new ExpressionResolver();
    }

    public function testResolvesRegisteredInstruction()
    {
        $action = function (NodeAdapterInterface $node) {
            return null;
        };

        $this->resolver->register('meh', ['adhoc', 'fail', $action]);

        $result = $this->resolver->resolve(['meh']);
        $this->assertInstanceOf(UserDefined::class, $result);
    }

    // @TODO: expand the test
}
