<?php

namespace JaneOlszewska\Tests\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Pair;
use JaneOlszewska\Itinerant\Instruction\Adhoc;
use PHPUnit\Framework\TestCase;

class AdhocTest extends TestCase
{
    private $fallbackExpression;
    private $node;

    protected function setUp()
    {
        $this->node = new Pair([1, [2, 3]]);
        $this->fallbackExpression = ['fallback'];
    }

    public function testExecutesApplicableAction()
    {
        $action = function (NodeAdapterInterface $node): NodeAdapterInterface {
            return $node;
        };
        $alwaysApplicable = function (NodeAdapterInterface $node): bool {
            return true;
        };
        $adhoc = new Adhoc($this->fallbackExpression, $action, $alwaysApplicable);

        $continuation = $adhoc->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals($this->node, $result);
    }

    public function testAppliesFallbackWhenActionInapplicable()
    {
        $action = function (NodeAdapterInterface $node): NodeAdapterInterface {
            return null;
        };
        $neverApplicable = function (NodeAdapterInterface $node): bool {
            return false;
        };

        $adhoc = new Adhoc($this->fallbackExpression, $action, $neverApplicable);

        $continuation = $adhoc->apply($this->node);

        $result = $continuation->current();
        $this->assertEquals([$this->fallbackExpression, $this->node], $result);

        // pretend $this->node was the result of applying $this->fallbackExpression to $this->>node
        $result = $continuation->send($this->node);
        $this->assertEquals($this->node, $result); // ...and adhoc resolves to fallback result
    }
}
