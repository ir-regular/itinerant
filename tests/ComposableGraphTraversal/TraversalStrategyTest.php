<?php

namespace JaneOlszewska\Experiments\Tests\ComposableGraphTraversal;

use JaneOlszewska\Experiments\ComposableGraphTraversal\Action;
use JaneOlszewska\Experiments\ComposableGraphTraversal\Datum;
use JaneOlszewska\Experiments\ComposableGraphTraversal\TraversalStrategy;
use PHPUnit\Framework\TestCase;

/**
 * identities:
 * s = seq(id, s) = choice(fail, s) = choice(s, fail)
 * fail = seq(fail, s) = seq(s, fail) = one(fail)
 * id = choice(id, s) = all(id)
 */
class TraversalStrategyTest extends TestCase
{
    /** @var TraversalStrategy */
    private $ts;

    protected function setUp()
    {
        parent::setUp();

        $this->ts = new TraversalStrategy();
    }

    public function testFail()
    {
        $node = $this->getNodeDatum();
        $fail = TraversalStrategy::getFail();

        $this->assertEquals($fail, $this->ts->apply(['fail'], $node));
    }

    public function testId()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($node, $this->ts->apply(['id'], $node));
    }

    public function testSeq()
    {
        $node = $this->getNodeDatum();
        $fail = TraversalStrategy::getFail();

        $this->assertEquals($fail, $this->ts->apply(['seq', [['fail'], ['id']]], $node));
        $this->assertEquals($fail, $this->ts->apply(['seq', [['id'], ['fail']]], $node));
        $this->assertEquals($node, $this->ts->apply(['seq', [['id'], ['id']]], $node));
    }

    public function testChoice()
    {
        $node = $this->getNodeDatum();
        $fail = TraversalStrategy::getFail();

        $this->assertEquals($node, $this->ts->apply(['choice', [['fail'], ['id']]], $node));
        $this->assertEquals($node, $this->ts->apply(['choice', [['id'], ['fail']]], $node));
        $this->assertEquals($node, $this->ts->apply(['choice', [['id'], ['id']]], $node));
        $this->assertEquals($fail, $this->ts->apply(['choice', [['fail'], ['fail']]], $node));
    }

    public function testAll()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());
        $fail = TraversalStrategy::getFail();

        $this->assertEquals($node, $this->ts->apply(['all', [['id']]], $node));
        $this->assertEquals($nodes, $this->ts->apply(['all', [['id']]], $nodes));
        $this->assertEquals($fail, $this->ts->apply(['all', [['fail']]], $nodes));
    }

    public function testOne()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());
        $fail = TraversalStrategy::getFail();

        $this->assertEquals($fail, $this->ts->apply(['one', [['fail']]], $node));
        $this->assertEquals($fail, $this->ts->apply(['one', [['fail']]], $nodes));
        $this->assertEquals($node, $this->ts->apply(['one', [['id']]], $nodes));
    }

    public function testAdhoc()
    {
        $fail = TraversalStrategy::getFail();

        $newName = 'modified!';
        $modifyAction = $this->getSetNameAction($newName);

        $modifiedNode = $this->getNodeDatum($newName);
        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, $newName));

        // adhoc on its own, not applying action and defaulting to 'fail' strategy

        $nodes = $this->getNodeArrayDatum([]);
        $this->assertEquals($fail, $this->ts->apply(['adhoc', [['fail'], $modifyAction]], $nodes));

        // adhoc on its own, not applying action and defaulting to 'id' strategy

        $this->assertEquals($nodes, $this->ts->apply(['adhoc', [['id'], $modifyAction]], $nodes));

        // adhoc on its own, applying action

        $node = $this->getNodeDatum();
        $this->assertEquals($modifiedNode, $this->ts->apply(['adhoc', [['fail'], $modifyAction]], $node));

        // todo: test adhoc where it substitutes with strategy (id/fail)

        // adhoc with all

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', [['adhoc', [['fail'], $modifyAction]]]], $nodes);
        $this->assertEquals($modifiedNodes, $result);

        // adhoc with one

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['one', [['adhoc', [['fail'], $modifyAction]]]], $nodes);
        $this->assertEquals($modifiedNode, $result);
    }

    public function testRegisterCustomStrategy()
    {
        $nodeOfNodes = $this->getNodeArrayDatum(
            [
                $this->getNodeArrayDatum($this->getNodes(2)),
                $this->getNodeArrayDatum($this->getNodes(2)),
            ]
        );
        $ordNodeOfNodes = $this->getNodeArrayDatum(
            [
                $this->getNodeArrayDatum([
                    $this->getNodeDatum('1'),
                    $this->getNodeDatum('2')
                ]),
                $this->getNodeArrayDatum([
                    $this->getNodeDatum('3'),
                    $this->getNodeDatum('4')
                ])
            ]
        );

        $ordAction = $this->getLabelWithOrdAction();

        // full_td(s) = seq(s, all(full_td(s)))
        $this->ts->registerStrategy('full_td', ['seq', ['0', ['all', [['full_td', ['0']]]]]], 1);
        $result = $this->ts->apply(['full_td', [['adhoc', [['id'], $ordAction]]]], $nodeOfNodes);
        $this->assertEquals($ordNodeOfNodes, $result);
    }

    /**
     * @param string $name
     *
     * @return Datum
     */
    private function getNodeDatum(string $name = 'node')
    {
        return new class($name) implements Datum
        {
            /** @var string */
            private $name;

            public function __construct($name)
            {
                $this->name = $name;
            }

            public function getChildren(): ?array
            {
                return null;
            }

            public function setChildren(array $children = []): void
            {
                // intentionally left empty
            }

            public function getName()
            {
                return $this->name;
            }

            public function setName(string $name)
            {
                $this->name = $name;
            }
        };
    }

    private function getNodes(int $nodeCount = 2, string $nodeName = 'node'): array
    {
        $nodes = [];

        for ($i = 0; $i < $nodeCount; $i++) {
            $nodes[] = $this->getNodeDatum($nodeName);
        }

        return $nodes;
    }

    /**
     * @param array $children
     * @return Datum
     */
    private function getNodeArrayDatum(array $children)
    {
        $n = new class($children) implements Datum
        {
            private $name = 'array'; // only here so that it's easily distinguishable in debug variables

            /** @var Datum[] */
            private $children;

            public function getChildren(): ?array
            {
                return $this->children;
            }

            public function setChildren(array $children = []): void
            {
                $this->children = $children;
            }
        };

        $n->setChildren($children);

        return $n;
    }

    private function getSetNameAction($newName)
    {
        return new class($newName) implements Action
        {
            private $newName;

            public function __construct($newName)
            {
                $this->newName = $newName;
            }

            public function isApplicableTo(Datum $d): bool
            {
                return method_exists($d, 'setName');
            }

            public function applyTo(Datum $d): Datum
            {
                $d->setName($this->newName);
                return $d;
            }
        };
    }

    private function getLabelWithOrdAction()
    {
        return new class implements Action
        {
            private static $ord = 1;

            public function isApplicableTo(Datum $d): bool
            {
                return method_exists($d, 'setName');
            }

            public function applyTo(Datum $d): Datum
            {
                $d->setName(self::$ord++);
                return $d;
            }
        };
    }
}
