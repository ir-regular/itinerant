<?php

namespace JaneOlszewska\Experiments\Tests\ComposableGraphTraversal;

use JaneOlszewska\Experiments\ComposableGraphTraversal\Action\ActionInterface;
use JaneOlszewska\Experiments\ComposableGraphTraversal\ChildHandler\ViaGetter;
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

    /** @var Datum */
    private $fail;

    protected function setUp()
    {
        parent::setUp();

        $childHandler = new ViaGetter();
        $this->fail = $this->getNodeDatum('fail');

        $this->ts = new TraversalStrategy($childHandler, $this->fail);
    }

    public function testFail()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($this->fail, $this->ts->apply('fail', $node));
    }

    public function testId()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($node, $this->ts->apply('id', $node));
    }

    public function testSeq()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($this->fail, $this->ts->apply(['seq', 'fail', 'id'], $node));
        $this->assertEquals($this->fail, $this->ts->apply(['seq', 'id', 'fail'], $node));
        $this->assertEquals($node, $this->ts->apply(['seq', 'id', 'id'], $node));
    }

    public function testChoice()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($node, $this->ts->apply(['choice', 'fail', 'id'], $node));
        $this->assertEquals($node, $this->ts->apply(['choice', 'id', 'fail'], $node));
        $this->assertEquals($node, $this->ts->apply(['choice', 'id', 'id'], $node));
        $this->assertEquals($this->fail, $this->ts->apply(['choice', 'fail', 'fail'], $node));
    }

    public function testAll()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $this->assertEquals($node, $this->ts->apply(['all', 'id'], $node));
        $this->assertEquals($nodes, $this->ts->apply(['all', 'id'], $nodes));
        $this->assertEquals($this->fail, $this->ts->apply(['all', 'fail'], $nodes));
    }

    public function testOne()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $this->assertEquals($this->fail, $this->ts->apply(['one', 'fail'], $node));
        $this->assertEquals($this->fail, $this->ts->apply(['one', 'fail'], $nodes));
        $this->assertEquals($node, $this->ts->apply(['one', 'id'], $nodes));
    }

    public function testAdhoc()
    {
        $newName = 'modified!';
        $modifyAction = $this->getSetNameAction($newName);

        $modifiedNode = $this->getNodeDatum($newName);
        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, $newName));

        // adhoc on its own, not applying action and defaulting to 'fail' strategy

        $nodes = $this->getNodeArrayDatum([]);
        $this->assertEquals($this->fail, $this->ts->apply(['adhoc', 'fail', $modifyAction], $nodes));

        // adhoc on its own, not applying action and defaulting to 'id' strategy

        $this->assertEquals($nodes, $this->ts->apply(['adhoc', 'id', $modifyAction], $nodes));

        // adhoc on its own, applying action

        $node = $this->getNodeDatum();
        $this->assertEquals($modifiedNode, $this->ts->apply(['adhoc', 'fail', $modifyAction], $node));

        // todo: test adhoc where it substitutes with strategy (id/fail)

        // adhoc with all

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', ['adhoc', 'fail', $modifyAction]], $nodes);
        $this->assertEquals($modifiedNodes, $result);

        // adhoc with one

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['one', ['adhoc', 'fail', $modifyAction]], $nodes);
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
        $this->ts->registerStrategy('full_td', ['seq', '0', ['all', ['full_td', '0']]], 1);
        $result = $this->ts->apply(['full_td', ['adhoc', 'id', $ordAction]], $nodeOfNodes);
        $this->assertEquals($ordNodeOfNodes, $result);
    }

    public function testApplyStrategyValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp("/Invalid argument structure for the strategy: .+/");
        $this->ts->apply('all', null);

        // todo: we could test all ways the validation should work... this is just the initial test
    }

    public function testRegisterStrategyValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp("/Invalid argument structure for the strategy: .+/");
        $this->ts->registerStrategy('broken', ['does_not_work', 'because it is', 'broken'], 0);

        // todo: again we could test all ways the validation should work... this is just the initial test
    }

    public function testAdhocWithCallableAction()
    {
        $newName = 'modified!';
        $modifyAction = function($node) use($newName) {
            $node->setName($newName);
            return $node;
        };

        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, $newName));

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', ['adhoc', 'fail', $modifyAction]], $nodes);
        $this->assertEquals($modifiedNodes, $result);
    }

    public function testAdhocWithNonApplicableCallableAction()
    {
        $nonApplicableAction = function($node) {
            return null;
        };

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', ['adhoc', 'fail', $nonApplicableAction]], $nodes);
        $this->assertEquals($this->fail, $result);
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
        /** @var Datum $n */
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
        return new class($newName) implements ActionInterface
        {
            private $newName;

            public function __construct($newName)
            {
                $this->newName = $newName;
            }

            public function isApplicableTo($d): bool
            {
                return method_exists($d, 'setName');
            }

            public function applyTo($d)
            {
                $d->setName($this->newName);
                return $d;
            }
        };
    }

    private function getLabelWithOrdAction()
    {
        return new class implements ActionInterface
        {
            private static $ord = 1;

            public function isApplicableTo($d): bool
            {
                return method_exists($d, 'setName');
            }

            public function applyTo($d)
            {
                $d->setName(self::$ord++);
                return $d;
            }
        };
    }
}
