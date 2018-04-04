<?php

namespace JaneOlszewska\Tests\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\SecondElement;
use JaneOlszewska\Itinerant\Strategy\Adhoc;
use PHPUnit\Framework\TestCase;

class AdhocTest extends TestCase
{
    private $fallbackInstruction;
    private $node;

    protected function setUp()
    {
        $this->node = new SecondElement([1, [2, 3]]);
        $this->fallbackInstruction = ['fallback'];
    }

    public function testExecutesApplicableAction()
    {
        $action = function (NodeAdapterInterface $node): ?NodeAdapterInterface {
            return $node;
        };
        $adhoc = new Adhoc($this->fallbackInstruction, $action);

        $continuation = $adhoc->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals($this->node, $result);
    }

    public function testFallsBackToStrategyOnInapplicableAction()
    {
        $action = function (NodeAdapterInterface $node): ?NodeAdapterInterface {
            return null; // null == inapplicable to this specific $node
        };
        $adhoc = new Adhoc($this->fallbackInstruction, $action);

        $continuation = $adhoc->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals([$this->fallbackInstruction, $this->node], $result);

        // pretend $this->node was the result of applying $this->fallbackInstruction to $this->>node
        $result = $continuation->send($this->node);
        $this->assertEquals($this->node, $result); // ...and adhoc resolves to fallback result
    }
}
