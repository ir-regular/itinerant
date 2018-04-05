<?php

namespace JaneOlszewska\Tests\Itinerant;

use JaneOlszewska\Itinerant\InstructionValidator;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Strategy\InstructionResolver;
use JaneOlszewska\Itinerant\StrategyStack;
use PHPUnit\Framework\TestCase;

class InstructionValidatorTest extends TestCase
{
    /** @var InstructionValidator */
    private $validator;


    protected function setUp()
    {
        parent::setUp();

        $this->validator = new InstructionValidator();
    }

    public function testSanitiseInbuiltZeroArgumentNodes()
    {
        $this->assertEquals(
            [InstructionResolver::FAIL],
            $this->validator->sanitiseApplied(InstructionResolver::FAIL)
        );

        $this->assertEquals(
            [InstructionResolver::ID],
            $this->validator->sanitiseApplied(InstructionResolver::ID)
        );
    }

    public function testSanitiseRegisteredZeroArgumentNodes()
    {
        $action = function (NodeAdapterInterface $node): ?NodeAdapterInterface {
            return null;
        };

        $this->assertEquals(
            ['adhoc', ['fail'], [$action]],
            $this->validator->sanitiseRegistered('meh', ['adhoc', 'fail', $action], 0)
        );
    }

    public function testValidatesArrayCallableActions()
    {
        $object = new class {
            public function f(NodeAdapterInterface $node): ?NodeAdapterInterface {
                return $node;
            }
        };

        $this->assertEquals(
            ['adhoc', ['fail'], [[$object, 'f']]],
            $this->validator->sanitiseApplied(['adhoc', 'fail', [$object, 'f']])
        );
    }

    public function testThrowsOnWrongArgumentCountProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Strategy all registered as accepting 1 argument, 0 provided');

        $this->validator->sanitiseApplied('all');
    }

    public function testThrowsOnUnregisteredStrategyApplication()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unregistered strategy: does_not_work');

        $this->validator->sanitiseRegistered('broken', ['does_not_work', 'because it is', 'broken'], 0);
    }

    public function testThrowsOnReRegisteringInbuiltStrategy()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot overwrite registered strategy key: id');

        $this->validator->sanitiseRegistered('id', ['fail'], 0);
    }

    public function testThrowsOnRegisteringActionWithIncorrectArgumentType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action must accept at least one argument, and it must be of type JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface');

        $action = function (): ?NodeAdapterInterface {
            return null;
        };

        $this->validator->sanitiseRegistered('meh', ['adhoc', 'fail', $action], 0);
    }

    public function testThrowsOnRegisteringActionWithIncorrectReturnType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action must return type ?JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface');

        // note that implicit return type is ok, but it's not explicitly declared and that's why it breaks
        $action = function (NodeAdapterInterface $node) {
            return $node;
        };

        $this->validator->sanitiseRegistered('meh', ['adhoc', 'fail', $action], 0);
    }

    public function testThrowsOnRegisteringStrategyWithNumericKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot register strategy under a numeric key: 1');

        $this->validator->sanitiseRegistered('1', ['all', 'id'], 0);
    }
}
