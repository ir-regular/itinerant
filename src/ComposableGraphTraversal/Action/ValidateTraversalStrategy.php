<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal\Action;

use JaneOlszewska\Experiments\ComposableGraphTraversal\ChildHandler\ChildHandlerInterface;
use JaneOlszewska\Experiments\ComposableGraphTraversal\TraversalStrategy;

/**
 * Action internal to library: validates every node of a traversal strategy supplied to TraversalStrategy::apply()
 */
class ValidateTraversalStrategy implements ActionInterface
{
    /** @var ChildHandlerInterface */
    protected $childHandler;

    /** @var int[] */
    protected $argumentCountsPerStrategyKey;

    /** @var string|null */
    protected $lastError;

    /**
     * @param ChildHandlerInterface $childHandler
     */
    public function __construct(ChildHandlerInterface $childHandler)
    {
        $this->childHandler = $childHandler;
    }

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

    /**
     * @param mixed $d
     * @return bool
     */
    public function isApplicableTo($d): bool
    {
        $isApplicable = true;

        if (!$this->isZeroArgumentNode($d)) { // is applicable to zero-argument nodes
            if (!$this->isAction($d)) { // is applicable to actions
                // ...if not a zero-argument node or action, check if valid strategy
                $isApplicable = $this->isValidStrategy($d);
            }
        }

        if (!$isApplicable) {
            $this->lastError = print_r($d, true); // todo: better error reporting
        }

        return $isApplicable;
    }

    public function applyTo($d)
    {
        return $d; // no application necessary: isApplicableTo combined with adhoc(fail,a) does all the heavy lifting
    }

    /**
     * @param mixed $d
     * @return bool
     */
    protected function isZeroArgumentNode($d): bool
    {
        return is_string($d)
            // inbuilt zero-argument strategies
            && (TraversalStrategy::FAIL == $d
                || TraversalStrategy::ID == $d
                // user-registered 0-argument strategies
                || (isset($this->argumentCountsPerStrategyKey[$d[0]])
                    && 0 == $this->argumentCountsPerStrategyKey[$d[0]]));
    }

    /**
     * @param mixed $d
     * @return bool
     */
    protected function isAction($d): bool
    {
        return ($d instanceof ActionInterface) || is_callable($d);
    }

    /**
     * @param $d
     * @return bool
     */
    protected function isValidStrategy($d): bool
    {
        $valid = false;

        // a node is a valid strategy if it is an array
        if (is_array($d)) {
            // ...and its key (first element) is included in the list of valid strategies
            if (isset($this->argumentCountsPerStrategyKey[$d[0]])) {
                // ...and its argument count matches the
                $argCount = $this->argumentCountsPerStrategyKey[$d[0]];
                $valid = ($argCount == count($this->childHandler->getChildren($d)));
            }
        }

        return $valid;
    }
}
