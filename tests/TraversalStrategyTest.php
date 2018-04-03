<?php

namespace JaneOlszewska\Tests\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\RestOfElements;
use JaneOlszewska\Itinerant\NodeAdapter\ViaGetter;
use JaneOlszewska\Itinerant\Strategy\Fail;
use JaneOlszewska\Itinerant\TraversalStrategy;
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

    /** @var object */
    private $fail;

    protected function setUp()
    {
        parent::setUp();

        $this->fail = Fail::fail();

        $this->ts = new TraversalStrategy();
    }

    public function testFail()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($this->fail, $this->ts->apply(['fail'], $node));
    }

    public function testId()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($node, $this->ts->apply(['id'], $node));
    }

    public function testSeq()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($this->fail, $this->ts->apply(['seq', ['fail'], ['id']], $node));
        $this->assertEquals($this->fail, $this->ts->apply(['seq', ['id'], ['fail']], $node));
        $this->assertEquals($node, $this->ts->apply(['seq', ['id'], ['id']], $node));
    }

    public function testChoice()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($node, $this->ts->apply(['choice', ['fail'], ['id']], $node));
        $this->assertEquals($node, $this->ts->apply(['choice', ['id'], ['fail']], $node));
        $this->assertEquals($node, $this->ts->apply(['choice', ['id'], ['id']], $node));
        $this->assertEquals($this->fail, $this->ts->apply(['choice', ['fail'], ['fail']], $node));
    }

    public function testAll()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $this->assertEquals($node, $this->ts->apply(['all', ['id']], $node));
        $this->assertEquals($nodes, $this->ts->apply(['all', ['id']], $nodes));
        $this->assertEquals($this->fail, $this->ts->apply(['all', ['fail']], $nodes));
    }

    public function testOne()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $this->assertEquals($this->fail, $this->ts->apply(['one', ['fail']], $node));
        $this->assertEquals($this->fail, $this->ts->apply(['one', ['fail']], $nodes));
        $this->assertEquals($node, $this->ts->apply(['one', ['id']], $nodes));
    }

    public function testAdhoc()
    {
        $newName = 'modified!';
        $modifyAction = $this->getSetNameAction($newName);

        $modifiedNode = $this->getNodeDatum($newName);
        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, $newName));

        // adhoc on its own, not applying action (because only applicable to nodes with 'getName')
        // and thus defaulting to 'fail' strategy

        $nodes = $this->getNodeArrayDatum([]);
        $this->assertEquals($this->fail, $this->ts->apply(['adhoc', ['fail'], $modifyAction], $nodes));

        // adhoc on its own, not applying action and defaulting to 'id' strategy

        $this->assertEquals($nodes, $this->ts->apply(['adhoc', ['id'], $modifyAction], $nodes));

        // adhoc on its own, applying action

        $node = $this->getNodeDatum();
        $this->assertEquals($modifiedNode, $this->ts->apply(['adhoc', ['fail'], $modifyAction], $node));

        // todo: test adhoc where it substitutes with strategy (id/fail)

        // adhoc with all

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', ['adhoc', ['fail'], $modifyAction]], $nodes);
        $this->assertEquals($modifiedNodes, $result);

        // adhoc with one

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['one', ['adhoc', ['fail'], $modifyAction]], $nodes);
        $this->assertEquals($modifiedNode, $result);
    }

    public function testRegisterCustomStrategy()
    {
        $nodeOfNodes = $this->getNodeArrayDatum(
            [
                $this->getUnwrappedNodeArrayDatum($this->getNodes(2)),
                $this->getUnwrappedNodeArrayDatum($this->getNodes(2)),
            ]
        );
        $ordNodeOfNodes = $this->getNodeArrayDatum(
            [
                $this->getUnwrappedNodeArrayDatum([
                    $this->getUnwrappedNodeDatum('1'),
                    $this->getUnwrappedNodeDatum('2')
                ]),
                $this->getUnwrappedNodeArrayDatum([
                    $this->getUnwrappedNodeDatum('3'),
                    $this->getUnwrappedNodeDatum('4')
                ])
            ]
        );

        $ordAction = $this->getLabelWithOrdAction();

        // full_td(s) = seq(s, all(full_td(s)))
        $this->ts->registerStrategy('full_td', ['seq', '0', ['all', ['full_td', '0']]], 1);
        $result = $this->ts->apply(['full_td', ['adhoc', ['id'], $ordAction]], $nodeOfNodes);
        $this->assertEquals($ordNodeOfNodes, $result);
    }

    public function testAdhocWithCallableAction()
    {
        $newName = 'modified!';
        $modifyAction = function (NodeAdapterInterface $node) use ($newName) {
            $node->getNode()->setName($newName);
            return $node;
        };

        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, $newName));

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', ['adhoc', 'fail', $modifyAction]], $nodes);
        $this->assertEquals($modifiedNodes, $result);
    }

    public function testAdhocWithCallableAsArrayAction()
    {
        $modifyAction = [$this, 'callableAction'];
        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, 'modified!'));

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', ['adhoc', 'fail', $modifyAction]], $nodes);
        $this->assertEquals($modifiedNodes, $result);
    }

    public function callableAction(NodeAdapterInterface $node)
    {
        $node->getNode()->setName('modified!');
        return $node;
    }

    public function testAdhocWithNonApplicableCallableAction()
    {
        $nonApplicableAction = function ($node) {
            return null;
        };

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->ts->apply(['all', ['adhoc', ['fail'], $nonApplicableAction]], $nodes);
        $this->assertEquals($this->fail, $result);
    }

    public function testAdhocSelectOneByAttribute()
    {
        $secondNode = $this->getUnwrappedNodeDatum('2');
        $ordNodeOfNodes = $this->getNodeArrayDatum(
            [
                $this->getUnwrappedNodeArrayDatum([
                    $this->getUnwrappedNodeDatum('1'),
                    $secondNode
                ]),
                $this->getUnwrappedNodeArrayDatum([
                    $this->getUnwrappedNodeDatum('3'),
                    $this->getUnwrappedNodeDatum('4')
                ])
            ]
        );
        // to use it as an argument on its own, I need to wrap it
        $secondNode = new ViaGetter($secondNode);

        // register 'attr' strategy: this is a rather roundabout way of creating a node-by-attribute selector.

        $action = new class
        {
            /** @var string */
            private $currentSearch;

            public function setCurrentSearch($s)
            {
                $this->currentSearch = $s;
            }

            public function __invoke(NodeAdapterInterface $d)
            {
                if (method_exists($d->getNode(), 'getName')
                    && ($d->getNode()->getName() == $this->currentSearch)
                ) {
                    return $d;
                }

                return null;
            }
        };

        $this->ts->registerStrategy('attr', ['adhoc', ['fail'], $action], 0);

        $action->setCurrentSearch('2'); // look for node with name == '2'
        $this->assertEquals($secondNode, $this->ts->apply(['attr'], $secondNode)); // yep, it matches

        // register strategy that traverses the graph top-down and return the first element that successfully fulfils
        // whatever strategy was provided as 1st arg

        $this->ts->registerStrategy('once_td', ['choice', '0', ['one', ['once_td', '0']]], 1);

        // perform the actual test: search for an element with name == '2'

        $result = $this->ts->apply(['once_td', ['attr']], $ordNodeOfNodes);
        $this->assertEquals($secondNode, $result);
    }

    /**
     * @param string $name
     * @param array  $children
     *
     * @return NodeAdapterInterface
     */
    private function getNodeDatum(string $name = 'node'): NodeAdapterInterface
    {
        $node = $this->getUnwrappedNodeDatum($name);
        return new ViaGetter($node);
    }

    private function getUnwrappedNodeDatum(string $name)
    {
        return new class($name)
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

            public function getValue()
            {
                return $this->getName();
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
            $nodes[] = $this->getUnwrappedNodeDatum($nodeName);
        }

        return $nodes;
    }

    private function getNodeArrayDatum(array $children): NodeAdapterInterface
    {
        $n = $this->getUnwrappedNodeArrayDatum($children);

        return new ViaGetter($n);
    }

    /**
     * Does not have 'setName', on purpose, so that adhoc actions do not apply to it
     *
     * @param array $children
     * @return object
     */
    private function getUnwrappedNodeArrayDatum(array $children)
    {
        $n = new class
        {
            private $name = 'array'; // only here so that it's easily distinguishable in debug variables

            private $children;

            public function getChildren(): ?array
            {
                return $this->children;
            }

            public function setChildren(array $children = []): void
            {
                $this->children = $children;
            }

            public function getValue()
            {
                return $this->name;
            }
        };

        $n->setChildren($children);

        return $n;
    }

    private function getSetNameAction($newName)
    {
        return new class($newName)
        {
            private $newName;

            public function __construct($newName)
            {
                $this->newName = $newName;
            }

            public function __invoke(NodeAdapterInterface $d)
            {
                if (method_exists($d->getNode(), 'setName')) {
                    $d->getNode()->setName($this->newName);
                    return $d;
                }

                return null;
            }
        };
    }

    private function getLabelWithOrdAction()
    {
        return new class
        {
            private static $ord = 1;

            public function __invoke(NodeAdapterInterface $d)
            {
                if (method_exists($d->getNode(), 'setName')) {
                    $d->getNode()->setName(self::$ord++);
                    return $d;
                }

                return null;
            }
        };
    }
}
