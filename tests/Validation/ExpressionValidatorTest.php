<?php

namespace JaneOlszewska\Tests\Itinerant\Validation;

use JaneOlszewska\Itinerant\Validation\ExpressionValidator;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Instruction\ExpressionResolver;
use PHPUnit\Framework\TestCase;

class ExpressionValidatorTest extends TestCase
{
    /** @var ExpressionValidator */
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = new ExpressionValidator();
    }

    public function testSanitiseInbuiltZeroArgumentNodes()
    {
        $this->assertEquals(
            [ExpressionResolver::FAIL],
            $this->validator->validate(ExpressionResolver::FAIL)
        );

        $this->assertEquals(
            [ExpressionResolver::ID],
            $this->validator->validate(ExpressionResolver::ID)
        );
    }

    public function testDoesNotWrapSubstitutions()
    {
        $instruction = [ExpressionResolver::CHOICE, '0', ExpressionResolver::FAIL];

        $this->assertEquals(
            [ExpressionResolver::CHOICE, '0', [ExpressionResolver::FAIL]],
            $this->validator->validateUserInstruction('x', $instruction)
        );
    }

    public function testSanitiseRegisteredZeroArgumentNodes()
    {
        $action = function (NodeAdapterInterface $node): NodeAdapterInterface {
            return $node;
        };
        $alwaysApplicable = function (NodeAdapterInterface $node): bool {
            return true;
        };

        $this->assertEquals(
            ['adhoc', ['fail'], [$action], [$alwaysApplicable]],
            $this->validator->validateUserInstruction('meh', ['adhoc', 'fail', $action])
        );
    }

    public function testValidatesArrayCallableActions()
    {
        $object = new class {
            public function f(NodeAdapterInterface $node): NodeAdapterInterface {
                return $node;
            }
        };
        $alwaysApplicable = function (NodeAdapterInterface $node): bool {
            return true;
        };

        $this->assertEquals(
            ['adhoc', ['fail'], [[$object, 'f']], [$alwaysApplicable]],
            $this->validator->validate(['adhoc', 'fail', [$object, 'f']])
        );
    }

    public function testThrowsOnWrongArgumentCountProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Instruction all registered as accepting 1 argument, 0 provided');

        $this->validator->validate('all');
    }

    public function testThrowsOnUnregisteredInstructionApplication()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unregistered instruction: does_not_work');

        $this->validator->validateUserInstruction('broken', ['does_not_work', 'because it is', 'broken']);
    }

    public function testThrowsOnOverwritingExistingInstruction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot overwrite existing instruction: id');

        $this->validator->validateUserInstruction('id', ['fail']);
    }

    public function testThrowsOnRegisteringActionWithIncorrectArgumentType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action must accept at least one argument, and it must be of type JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface');

        $action = function (): NodeAdapterInterface {
            return null;
        };

        $this->validator->validateUserInstruction('meh', ['adhoc', 'fail', $action]);
    }

    public function testThrowsOnRegisteringActionWithIncorrectReturnType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Actions must return type JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface and isApplicable callables must return bool');

        // note that implicit return type is ok, but it's not explicitly declared and that's why it breaks
        $action = function (NodeAdapterInterface $node) {
            return $node;
        };

        $this->validator->validateUserInstruction('meh', ['adhoc', 'fail', $action]);
    }

    public function testThrowsOnRegisteringInstructionWithNumericName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot register instruction with a numeric name: 1');

        $this->validator->validateUserInstruction('1', ['all', 'id']);
    }

    public function testThrowsOnIntsMissingFromSubstitutionSequence()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot register instruction: s. Non-contiguous substitution sequence: [0, 2]');

        $this->validator->validateUserInstruction(
            's',
            ['seq', '0', '2']
        );
    }
}
