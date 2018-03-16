<?php

namespace JaneOlszewska\Itinerant\Strategy;

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

    public function __invoke($previousResult, $s, $a)
    {
        $applied = false;
        $res = null; // non-terminal by default

        if (is_callable($a)) {
            // strategy resolved to applied action; terminal unless null returned
            // todo: document this clearly somewhere
            $res = $a($previousResult);
            $applied = ($res !== null);
        }

        if (!$applied) {
            $this->stack->pop(); // remove self, fully resolved
            $this->stack->push($s, $previousResult); // resolve strategy $s with $d
        }

        return $res;
    }
}
