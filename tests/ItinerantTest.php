<?php

namespace JaneOlszewska\Tests\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\Strategy\StrategyResolver;
use JaneOlszewska\Itinerant\Itinerant;
use PHPUnit\Framework\TestCase;

class ItinerantTest extends TestCase
{
    /** @var Itinerant */
    private $ts;

    /** @var object */
    private $fail;

    protected function setUp()
    {
        parent::setUp();

        $this->fail = Fail::fail();

        $this->ts = new Itinerant();
    }

    public function testSanitisationForInbuiltSingleArgumentNodes()
    {
        $node = ['good node', []];
        $node = new SecondElement($node);

        // note we no longer have to enclose zero-argument strategies in arrays

        $this->assertEquals($this->fail, $this->ts->apply(StrategyResolver::FAIL, $node));
        $this->assertEquals($node, $this->ts->apply(StrategyResolver::ID, $node));
    }

    public function testSanitisationForRegisteredSingleArgumentNodes()
    {
        $action = function (NodeAdapterInterface $node): ?NodeAdapterInterface {
            $value = 'whatever';
            return new SecondElement($value);
        };

        $nodes = [
            'root node', [
                ['good node', []],
                ['good node', []],
            ]
        ];
        $nodes = new SecondElement($nodes);

        // note we don't have to enclose either 'fail' or 'meh' in []

        $this->ts->registerStrategy('meh', ['adhoc', 'fail', $action], 0);
        $result = $this->ts->apply(['all', 'meh'], $nodes);
        $this->assertEquals(['root node', ['whatever', 'whatever']], $result->getNode());
    }

    public function testApplyStrategyValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Strategy all registered as accepting 1 argument, 0 provided');

        $node = null;
        $this->ts->apply('all', new SecondElement($node));

        // todo: we could test all ways the validation should work... this is just the initial test
    }

    public function testRegisterStrategyValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unregistered strategy: does_not_work');
        $this->ts->registerStrategy('broken', ['does_not_work', 'because it is', 'broken'], 0);

        // todo: again we could test all ways the validation should work... this is just the initial test
    }

    public function testCannotReRegisterInbuiltStrategy()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot overwrite registered strategy key: id');
        $this->ts->registerStrategy('id', ['fail'], 0);
    }

    public function testCannotRegisterActionWithIncorrectParameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action must accept at least one argument, and it must be of type JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface');

        $action = function (): ?NodeAdapterInterface {
            return null;
        };

        $this->ts->registerStrategy('meh', ['adhoc', 'fail', $action], 0);
    }

    public function testCannotRegisterActionWithIncorrectReturnType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action must return type ?JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface');

        // note that implicit return type is ok, but it's not explicitly declared and that's why it breaks
        $action = function (NodeAdapterInterface $node) {
            return $node;
        };

        $this->ts->registerStrategy('meh', ['adhoc', 'fail', $action], 0);
    }

    public function testCannotRegisterStrategyWithNumericKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot register strategy under a numeric key: 1');

        $this->ts->registerStrategy('1', ['all', 'id'], 0);
    }
}
