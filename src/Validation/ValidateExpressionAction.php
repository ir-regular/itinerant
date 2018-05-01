<?php

namespace JaneOlszewska\Itinerant\Validation;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Sequence;
use JaneOlszewska\Itinerant\Instruction\ExpressionResolver;

/**
 * Internal library action: validates expressions passed to Itinerant::apply()
 *
 * ValidateExpressionAction validates every node of the expression by checking that:
 *   - the node is an array
 *   - the first element is a string, which is the name a registered instruction
 *   - count of the rest of elements equals argument count of the instruction
 *
 * For readability, the above ruleset has an exception. Zero-argument instructions
 * (id, fail, and any user-registered zero argument instructions) can be represented
 * as strings instead of single-element arrays.
 *
 * ValidateExpressionAction converts the strings into single-element arrays.
 */
class ValidateExpressionAction
{
    /** @var int[] */
    protected $instructionArgumentCounts;

    /** @var string|null */
    protected $validationError;

    /** @var NodeAdapterInterface|null */
    private $invalidNode;

    public function __construct(array $instructionArgumentCounts = [])
    {
        $this->instructionArgumentCounts = $instructionArgumentCounts;
    }

    /**
     * @return NodeAdapterInterface|null
     */
    public function getInvalidNode(): ?NodeAdapterInterface
    {
        return $this->invalidNode;
    }

    /**
     * @return string|null
     */
    public function getValidationError(): ?string
    {
        return $this->validationError;
    }

    public function __invoke(NodeAdapterInterface $d): NodeAdapterInterface
    {
        if ($this->isZeroArgumentShorthand($d)) {
            // If special case of zero-argument instruction applies:
            // sanitise instruction (convert 'string' to ['string']).
            return $this->convertZeroArgumentShorthand($d);

        } elseif ($this->isAction($d)) {
            // If action (callable):
            // ensure correct argument/return types.
            $isValid = $this->isValidAction($d);

        } elseif ($this->isAlwaysApplicableAdhoc($d)) {
            return $this->convertAlwaysApplicableAdhoc($d);

        } else {
            // ...otherwise it must be an expression.
            // check that provided argument count equals expected instruction argument count
            $isValid = $this->isValidExpression($d);
        }

        if ($isValid) {
            return $d;
        } else {
            $this->invalidNode = $d;
            return Fail::fail();
        }
    }

    /**
     * @param NodeAdapterInterface $d
     * @return bool
     */
    protected function isZeroArgumentShorthand(NodeAdapterInterface $d): bool
    {
        $instruction = $d->getValue();

        return is_string($instruction)
            // inbuilt zero-argument instructions
            && (ExpressionResolver::FAIL == $instruction
                || ExpressionResolver::ID == $instruction
                // user-registered 0-argument instructions
                || (0 === ($this->instructionArgumentCounts[$instruction] ?? null)));
    }

    /**
     * @param NodeAdapterInterface $d
     * @return bool
     */
    protected function isAction(NodeAdapterInterface $d): bool
    {
        return is_callable($d->getValue());
    }

    protected function isAlwaysApplicableAdhoc(NodeAdapterInterface $d): bool
    {
        return ($d->getValue() === ExpressionResolver::ADHOC)
            && iterator_count($d->getChildren()) == 2; // missing the 'is applicable' argument
    }

    /**
     * Handroll type checking *le sigh*
     *
     * @param NodeAdapterInterface $d
     * @return bool
     */
    protected function isValidAction(NodeAdapterInterface $d): bool
    {
        $action = $d->getValue();

        try {
            if (is_array($action)) {
                // expecting [$object, 'method]
                $reflection = new \ReflectionMethod(...$action);
            } else {
                // string (global function name), closure or an object with function __invoke()
                $reflection = new \ReflectionFunction($action);
            }
        } catch (\ReflectionException $e) {
            $this->validationError = 'Cannot reflect on provided action';
            return false; // Shouldn't happen, but, y'know ¯\_(ツ)_/¯
        }

        return $this->isValidActionReturnType($reflection)
            && $this->isActionArgumentValid($reflection);
    }

    /**
     * @param \ReflectionFunctionAbstract $reflection
     * @return bool
     */
    protected function isValidActionReturnType(\ReflectionFunctionAbstract $reflection): bool
    {
        $returnTypeValid = ($returnType = $reflection->getReturnType())
            // action - or - is applicable
            && ($returnType == NodeAdapterInterface::class || $returnType == 'bool')
            && !$returnType->allowsNull();

        // You may be wondering why I didn't hardcode the class in the error messages.
        // This way if I ever want to rename it, automatic refactoring tools will work correctly.

        if (!$returnTypeValid) {
            $this->validationError = 'Actions must return type '
                . NodeAdapterInterface::class . ' and isApplicable callables must return bool';
        }

        return $returnTypeValid;
    }

    /**
     * @param \ReflectionFunctionAbstract $reflection
     * @return bool
     */
    protected function isActionArgumentValid(\ReflectionFunctionAbstract $reflection): bool
    {
        $argumentValid = ($reflection->getNumberOfParameters() >= 1)
            && ($argumentType = $reflection->getParameters()[0]->getType())
            && ($argumentType == NodeAdapterInterface::class);

        if (!$argumentValid) {
            $this->validationError = 'Action must accept at least one argument, and it must be of type '
                . NodeAdapterInterface::class;
        }
        return $argumentValid;
    }

    /**
     * @param NodeAdapterInterface $d
     * @return bool
     */
    protected function isValidExpression(NodeAdapterInterface $d): bool
    {
        $valid = false;

        // an expression is a valid instruction application if...
        $instruction = $d->getValue();

        // ...its main node (instruction, first element of array, car) is on list of known instructions
        if (isset($this->instructionArgumentCounts[$instruction])) {
            // ...and the count of its children (arguments, rest of the array, cdr) matches expected count
            $expectedCount = $this->instructionArgumentCounts[$instruction];
            $actualCount = iterator_count($d->getChildren());
            $valid = ($expectedCount == $actualCount);

            if (!$valid) {
                $expectedCount .= ($expectedCount < 2) ? ' argument' : ' arguments';

                $this->validationError = "Instruction {$instruction} registered as accepting {$expectedCount}"
                    . ", {$actualCount} provided";
            }
        } else {
            // this shouldn't ever happen but just in case ¯\_(ツ)_/¯
            $this->validationError = "Unregistered instruction: {$instruction}";
        }

        return $valid;
    }

    protected function convertZeroArgumentShorthand(NodeAdapterInterface $d)
    {
        $sanitisedNode = [$d->getValue()];
        return new Sequence($sanitisedNode);
    }

    protected function convertAlwaysApplicableAdhoc(NodeAdapterInterface $d)
    {
        $sanitisedNode = [$d->getValue()];
        foreach ($d->getChildren() as $child) {
            $sanitisedNode[] = $child->getValue();
        }
        $sanitisedNode[] = function (NodeAdapterInterface $node): bool {
            return true;
        };
        return new Sequence($sanitisedNode);
    }
}
