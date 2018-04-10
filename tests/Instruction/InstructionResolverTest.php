<?php

namespace JaneOlszewska\Tests\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\Instruction\InstructionResolver;
use JaneOlszewska\Itinerant\Instruction\UserDefined;
use PHPUnit\Framework\TestCase;

class InstructionResolverTest extends TestCase
{
    /** @var InstructionResolver */
    private $resolver;

    protected function setUp()
    {
        parent::setUp();

        $this->resolver = new InstructionResolver();
    }

    public function testRegisterStrategy()
    {
        $action = function (NodeAdapterInterface $node) {
            return null;
        };

        $this->resolver->registerStrategy('meh', ['adhoc', 'fail', $action]);

        $result = $this->resolver->resolve(['meh']);
        $this->assertInstanceOf(UserDefined::class, $result);
    }

    // @TODO: expand the test
}
