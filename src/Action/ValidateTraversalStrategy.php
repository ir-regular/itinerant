<?php

namespace JaneOlszewska\Itinerant\Action;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\RestOfElements;
use JaneOlszewska\Itinerant\TraversalStrategy;

/**
 * Action internal to library: validates every node of a traversal strategy supplied to TraversalStrategy::apply()
 */
class ValidateTraversalStrategy
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
        $isValid = true;

        if (!$this->isZeroArgumentNode($d)) { // is applicable to zero-argument nodes
            if (!$this->isAction($d)) { // is applicable to actions
                // ...if not a zero-argument node or action, check if valid strategy
                $isValid = $this->isValidStrategy($d);
            }
        } else {
            return $this->sanitiseZeroArgumentNode($d);
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
            && (TraversalStrategy::FAIL == $strategy
                || TraversalStrategy::ID == $strategy
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
