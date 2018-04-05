<?php

namespace JaneOlszewska\Itinerant\Action;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\RestOfElements;
use JaneOlszewska\Itinerant\Strategy\StrategyResolver;

/**
 * Internal library action: validates and sanitises instructions given to Itinerant::apply()
 *
 * SanitiseAppliedAction validates every node of the instructions by checking that:
 *   - the node is an array
 *   - its first element is a string key, on the list of registered actions
 *   - array element count equals (registered argument count + 1)
 *
 * For readability, the above ruleset has an exception. Zero-argument strategies can be represented
 * as strings (keys) instead of single-element arrays.
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

    /**
     * @param int[] $argumentCountsPerStrategyKey
     */
    public function setStrategyArgumentCounts(array $argumentCountsPerStrategyKey): void
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
        if ($this->isZeroArgumentNode($d)) { // is applicable to zero-argument nodes
            return $this->sanitiseZeroArgumentNode($d);
        } else {
            if ($this->isAction($d)) { // is applicable to actions
                $isValid = $this->isValidAction($d);
            } else {
                // ...if not a zero-argument node or action, check if valid strategy
                $isValid = $this->isValidStrategy($d);
            }
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
    protected function isZeroArgumentNode(NodeAdapterInterface $d): bool
    {
        $strategy = $d->getValue();

        return is_string($strategy)
            // inbuilt zero-argument strategies
            && (StrategyResolver::FAIL == $strategy
                || StrategyResolver::ID == $strategy
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
    protected function isValidStrategy(NodeAdapterInterface $d): bool
    {
        $valid = false;

        // a node is a valid strategy if...
        $strategy = $d->getValue();

        // ...its main node (key) is included in the list of valid strategies
        if (isset($this->argumentCountsPerStrategyKey[$strategy])) {
            // ...and the count of its children (arguments) count matches the registered count
            $expectedCount = $this->argumentCountsPerStrategyKey[$strategy];
            $actualCount = iterator_count($d->getChildren());
            $valid = ($expectedCount == $actualCount);

            if (!$valid) {
                $expectedCount .= ($expectedCount < 2) ? ' argument' : ' arguments';

                $this->validationError = "Strategy {$strategy} registered as accepting {$expectedCount}"
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
        return new RestOfElements($sanitisedNode);
    }
}
