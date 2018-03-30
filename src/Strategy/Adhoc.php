<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\StrategyStack;

class Adhoc
{
    /**
     * @var StrategyStack
     */
    private $stack;

    public function __construct(StrategyStack $stack)
    {
        $this->stack = $stack;
    }

    public function __invoke(NodeAdapterInterface $previousResult, $s, $a): ?NodeAdapterInterface
    {
        $applied = false;
        $res = null; // non-terminal by default

        if (is_callable($a)) {
            // strategy resolved to applied action; terminal unless null returned
            // @TODO: document this clearly somewhere
            $res = $a($previousResult);
            $applied = ($res !== null);
        }

        if ($applied) {
            // @TODO: it would be better to check this elsewhere
            if (!($res instanceof NodeAdapterInterface)) {
                throw new \UnexpectedValueException('Adhoc callable result must be a NodeAdapterInterface');
            }
        } else {
            $this->stack->pop(); // remove self, fully resolved
            $this->stack->push($s, $previousResult); // resolve strategy $s with $previousResult
        }

        return $res;
    }
}
