<?php

namespace JaneOlszewska\Itinerant\Action;

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
    protected $lastError;

    /**
     * @param int[] $argumentCountsPerStrategyKey
     */
    public function setStrategyArgumentCounts(array $argumentCountsPerStrategyKey): void
    {
        $this->argumentCountsPerStrategyKey = $argumentCountsPerStrategyKey;
    }

    /**
     * @return string
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
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
            $this->lastError = print_r($d, true); // todo: better error reporting
            return null;
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
                // closure or an object with function __invoke()
                $reflection = new \ReflectionFunction($action);
            }
        } catch (\ReflectionException $e) {
            return false; // Shouldn't happen, but, y'know ¯\_(ツ)_/¯
        }

        $returnTypeValid = ($returnType = $reflection->getReturnType())
            && ($returnType == NodeAdapterInterface::class)
            && ($returnType->allowsNull() == true);

        $parametersValid = ($reflection->getNumberOfParameters() >= 1)
            && ($parameterType = $reflection->getParameters()[0]->getType())
            && ($parameterType == NodeAdapterInterface::class);

        return $returnTypeValid && $parametersValid;
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
            $argCount = $this->argumentCountsPerStrategyKey[$strategy];
            $arguments = $d->getChildren();
            $valid = ($argCount == iterator_count($arguments));
        }

        return $valid;
    }

    protected function sanitiseZeroArgumentNode(NodeAdapterInterface $d)
    {
        $sanitisedNode = [$d->getValue()];
        return new RestOfElements($sanitisedNode);
    }
}
