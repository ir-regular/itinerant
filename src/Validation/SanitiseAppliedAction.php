<?php

namespace JaneOlszewska\Itinerant\Validation;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Sequence;
use JaneOlszewska\Itinerant\Instruction\InstructionResolver;

/**
 * Internal library action: validates and sanitises instructions given to Itinerant::apply()
 *
 * SanitiseAppliedAction validates every node of the instructions by checking that:
 *   - the node is an array
 *   - its first element is a string key, on the list of registered actions
 *   - array element count equals (registered argument count + 1)
 *
 * For readability, the above ruleset has an exception. Zero-argument strategies can be represented
 * as instructions as strings (keys) instead of single-element arrays.
 *
 * SanitiseAppliedAction sanitises such strings by converting them into single-element arrays.
 */
class SanitiseAppliedAction
{
    /** @var int[] */
    protected $argumentCountsPerStrategyKey;

    /** @var string|null */
    protected $validationError;

    /** @var NodeAdapterInterface|null */
    private $invalidNode;

    public function __construct(array $argumentCountsPerStrategyKey = [])
    {
        $this->argumentCountsPerStrategyKey = $argumentCountsPerStrategyKey;
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

    public function __invoke(NodeAdapterInterface $d): ?NodeAdapterInterface
    {
        if ($this->isZeroArgumentStrategy($d)) {
            // If special case of zero-argument strategy applies:
            // sanitise instruction (if string, convert to [string]).
            return $this->sanitiseZeroArgumentNode($d);
        } elseif ($this->isAction($d)) {
            // If action (callable):
            // ensure correct argument/return types.
            $isValid = $this->isValidAction($d);
        } else {
            // ...otherwise it must be an instruction.
            // check that count of arguments provided matches count of arguments expected by strategy.
            $isValid = $this->isValidInstruction($d);
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
    protected function isZeroArgumentStrategy(NodeAdapterInterface $d): bool
    {
        $strategy = $d->getValue();

        return is_string($strategy)
            // inbuilt zero-argument strategies
            && (InstructionResolver::FAIL == $strategy
                || InstructionResolver::ID == $strategy
                // user-registered 0-argument strategies
                || (isset($this->argumentCountsPerStrategyKey[$strategy])
                    && 0 == $this->argumentCountsPerStrategyKey[$strategy]));
    }

    /**
     * @param NodeAdapterInterface $d
     * @return bool
     */
    protected function isAction(NodeAdapterInterface $d): bool
    {
        return is_callable($d->getValue());
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
            && ($returnType == NodeAdapterInterface::class)
            && ($returnType->allowsNull() == true);

        // You may be wondering why I didn't hardcode the class in the error messages.
        // This way if I ever want to rename it, automatic refactoring tools will work correctly.

        if (!$returnTypeValid) {
            $this->validationError = 'Action must return type ?'
                . NodeAdapterInterface::class;
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
    protected function isValidInstruction(NodeAdapterInterface $d): bool
    {
        $valid = false;

        // an instruction is a valid strategy application if...
        $strategy = $d->getValue();

        // ...its main node (strategy key, first element of array, car) is on registered list
        if (isset($this->argumentCountsPerStrategyKey[$strategy])) {
            // ...and the count of its children (arguments, rest of the array, cdr) matches expected count
            $expectedCount = $this->argumentCountsPerStrategyKey[$strategy];
            $actualCount = iterator_count($d->getChildren());
            $valid = ($expectedCount == $actualCount);

            if (!$valid) {
                $expectedCount .= ($expectedCount < 2) ? ' argument' : ' arguments';

                $this->validationError = "Instruction {$strategy} registered as accepting {$expectedCount}"
                    . ", {$actualCount} provided";
            }
        } else {
            // this shouldn't ever happen but just in case ¯\_(ツ)_/¯
            $this->validationError = "Unregistered strategy: {$strategy}";
        }

        return $valid;
    }

    protected function sanitiseZeroArgumentNode(NodeAdapterInterface $d)
    {
        $sanitisedNode = [$d->getValue()];
        return new Sequence($sanitisedNode);
    }
}
