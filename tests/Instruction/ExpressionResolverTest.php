<?php

namespace IrRegular\Tests\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;
use IrRegular\Itinerant\NodeAdapter\Fail;
use IrRegular\Itinerant\Instruction\ExpressionResolver;
use IrRegular\Itinerant\Instruction\UserDefined;
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
