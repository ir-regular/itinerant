<?php

namespace JaneOlszewska\Tests\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\Strategy\StrategyResolver;
use JaneOlszewska\Itinerant\Strategy\UserDefined;
use PHPUnit\Framework\TestCase;

class StrategyResolverTest extends TestCase
{
    /** @var NodeAdapterInterface */
    private $fail;

    /** @var StrategyResolver */
    private $resolver;

    protected function setUp()
    {
        parent::setUp();

        $this->fail = Fail::fail();

        $this->resolver = new StrategyResolver();
    }

    public function testRegisterStrategy()
    {
        $action = function (NodeAdapterInterface $node) {
            return null;
        };

        // note we don't have to enclose either 'fail' or 'meh' in [] because they'll auto-sanitise

        $this->resolver->registerStrategy('meh', ['adhoc', 'fail', $action]);

        $result = $this->resolver->resolve(['meh']);
        $this->assertInstanceOf(UserDefined::class, $result);
    }
}
