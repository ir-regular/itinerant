<?php

namespace JaneOlszewska\Tests\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Accessor;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\Instruction\ExpressionResolver;
use JaneOlszewska\Itinerant\Instruction\InstructionStack;
use PHPUnit\Framework\TestCase;

/**
 * identities:
 * s = seq(id, s) = choice(fail, s) = choice(s, fail)
 * fail = seq(fail, s) = seq(s, fail) = one(fail)
 * id = choice(id, s) = all(id)
 */
class InstructionStackTest extends TestCase
{
    /** @var InstructionStack */
    private $stack;

    /** @var object */
    private $fail;

    protected function setUp()
    {
        parent::setUp();

        $this->fail = Fail::fail();

        $resolver = $this->getInitialisedInstructionResolver();

        $this->stack = new InstructionStack($resolver);
    }

    public function testFail()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($this->fail, $this->stack->apply(['fail'], $node));
    }

    public function testId()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($node, $this->stack->apply(['id'], $node));
    }

    public function testSeq()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($this->fail, $this->stack->apply(['seq', ['fail'], ['id']], $node));
        $this->assertEquals($this->fail, $this->stack->apply(['seq', ['id'], ['fail']], $node));
        $this->assertEquals($node, $this->stack->apply(['seq', ['id'], ['id']], $node));
    }

    public function testChoice()
    {
        $node = $this->getNodeDatum();

        $this->assertEquals($node, $this->stack->apply(['choice', ['fail'], ['id']], $node));
        $this->assertEquals($node, $this->stack->apply(['choice', ['id'], ['fail']], $node));
        $this->assertEquals($node, $this->stack->apply(['choice', ['id'], ['id']], $node));
        $this->assertEquals($this->fail, $this->stack->apply(['choice', ['fail'], ['fail']], $node));
    }

    public function testAll()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $this->assertEquals($node, $this->stack->apply(['all', ['id']], $node));
        $this->assertEquals($nodes, $this->stack->apply(['all', ['id']], $nodes));
        $this->assertEquals($this->fail, $this->stack->apply(['all', ['fail']], $nodes));
    }

    public function testOne()
    {
        $node = $this->getNodeDatum();
        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $this->assertEquals($this->fail, $this->stack->apply(['one', ['fail']], $node));
        $this->assertEquals($this->fail, $this->stack->apply(['one', ['fail']], $nodes));
        $this->assertEquals($node, $this->stack->apply(['one', ['id']], $nodes));
    }

    public function testAdhoc()
    {
        $newName = 'modified!';
        $modifyAction = $this->getSetNameAction($newName);

        $unwrappedNode = $this->getUnwrappedNodeDatum('original');
        $node = new Accessor($unwrappedNode);
        $modifiedNode = $this->getNodeDatum($newName);

        $unwrappedNodes = $this->getUnwrappedNodeArrayDatum($this->getNodes());
        $nodes = new Accessor($unwrappedNodes);
        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, $newName));

        // adhoc on its own, not applying action (because only applicable to nodes with 'getName')
        // and thus defaulting to 'fail' instruction

        $result = $this->stack->apply(['adhoc', ['fail'], $modifyAction], $this->getNodeArrayDatum([]));
        $this->assertEquals($this->fail, $result);

        // adhoc on its own, not applying action and defaulting to 'id' instruction

        $this->assertEquals($nodes, $this->stack->apply(['adhoc', ['id'], $modifyAction], $nodes));

        // adhoc on its own, applying action

        $result = $this->stack->apply(['adhoc', ['fail'], $modifyAction], $node);
        $this->assertEquals($modifiedNode, $result);
        $this->assertNotEquals($unwrappedNode, $result->getNode()); // original data was not modified in the process

        // todo: test adhoc where it substitutes with instruction (id/fail)

        // adhoc with all

        $result = $this->stack->apply(['all', ['adhoc', ['fail'], $modifyAction]], $nodes);
        $this->assertEquals($modifiedNodes, $result);
        $this->assertNotEquals($unwrappedNodes, $result->getNode()); // original data was not modified in the process

        // adhoc with one

        $result = $this->stack->apply(['one', ['adhoc', ['fail'], $modifyAction]], $nodes);
        $this->assertEquals($modifiedNode, $result);
        $this->assertNotEquals($unwrappedNodes, $result->getNode()); // original data was not modified in the process
    }

    public function testModificationStoppedHalfway()
    {
        $nodes = $this->getNodeArrayDatum($this->getNodes());
        $modifyAction = function (NodeAdapterInterface $d) {
            static $counter = 0;

            if (method_exists($d->getNode(), 'setName')) {
                if (++$counter == 2) {
                    return null; // "doesn't apply" to 2nd child
                }
                $d->getNode()->setName('modified!');
                return $d;
            }

            return null;
        };

        $result = $this->stack->apply(['choice',
            // modify action "doesn't apply" on 2nd child,
            // thus adhoc calls 'fail' on 2nd child
            // thus all fails
            ['all', ['adhoc', ['fail'], $modifyAction]],
            // therefore, return the original node provided to 'choice'
            ['id']
        ], $nodes);

        $this->assertEquals($nodes, $result); // check that result is unmodified
    }

    public function testUserDefinedInstruction()
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

        $result = $this->stack->apply(['full_td', ['adhoc', ['id'], $ordAction]], $nodeOfNodes);
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

        $result = $this->stack->apply(['all', ['adhoc', ['fail'], $modifyAction]], $nodes);
        $this->assertEquals($modifiedNodes, $result);
    }

    public function testAdhocWithCallableAsArrayAction()
    {
        $modifyAction = [$this, 'callableAction'];
        $modifiedNodes = $this->getNodeArrayDatum($this->getNodes(2, 'modified!'));

        $nodes = $this->getNodeArrayDatum($this->getNodes());

        $result = $this->stack->apply(['all', ['adhoc', ['fail'], $modifyAction]], $nodes);
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

        $result = $this->stack->apply(['all', ['adhoc', ['fail'], $nonApplicableAction]], $nodes);
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
        $secondNode = new Accessor($secondNode);

        $this->assertEquals($secondNode, $this->stack->apply(['attr'], $secondNode)); // yep, it matches


        // perform the actual test: search for an element with name == '2'

        $result = $this->stack->apply(['once_td', ['attr']], $ordNodeOfNodes);
        $this->assertEquals($secondNode, $result);
    }

    /**
     * @param string $name
     *
     * @return NodeAdapterInterface
     */
    private function getNodeDatum(string $name = 'node'): NodeAdapterInterface
    {
        $node = $this->getUnwrappedNodeDatum($name);
        return new Accessor($node);
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

        return new Accessor($n);
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

    private function getNameMatchAction()
    {
        return new class
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
    }

    /**
     * @return ExpressionResolver
     */
    private function getInitialisedInstructionResolver(): ExpressionResolver
    {
        $resolver = new ExpressionResolver();

        // full_td(s) = seq(s, all(full_td(s)))

        $resolver->register('full_td', ['seq', '0', ['all', ['full_td', '0']]]);

        // register 'attr' instruction: this is a rather roundabout way of creating a node-by-attribute selector.

        $action = $this->getNameMatchAction();
        $action->setCurrentSearch('2'); // look for node with name == '2'
        $resolver->register('attr', ['adhoc', ['fail'], $action]);

        // register instruction that traverses the graph top-down and return the first element that successfully fulfils
        // whatever instruction was provided as 1st arg

        $resolver->register('once_td', ['choice', '0', ['one', ['once_td', '0']]]);
        return $resolver;
    }
}
