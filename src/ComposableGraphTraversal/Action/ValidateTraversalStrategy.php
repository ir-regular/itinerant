<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal\Action;

use JaneOlszewska\Experiments\ComposableGraphTraversal\TraversalStrategy;

/**
 * Action internal to library: validates every node of a traversal strategy supplied to TraversalStrategy::apply()
 */
class ValidateTraversalStrategy implements ActionInterface
{
    /** @var string */
    private $lastError;

    /**
     * @return string
     */
    public function getLastError()
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

        if (!$this->isZeroArgumentNode($d)) {
            if (!$this->isAction($d)) {
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
        // todo: must allow for user-registered 0-argument strategies
        return is_string($d) && ($d == TraversalStrategy::FAIL || $d == TraversalStrategy::ID);
    }

    /**
     * @param mixed $d
     * @return bool
     */
    protected function isAction($d): bool
    {
        return $d instanceof ActionInterface; // todo: allow Callable with a single argument
    }

    /**
     * @param $d
     * @return bool
     */
    protected function isValidStrategy($d): bool
    {
        $isStrategy = is_array($d);
        // todo: and check $d[0] is one of available strategies
        // todo: and check the number of arguments (count($d) - 1) is correct
        return $isStrategy;
    }
}
