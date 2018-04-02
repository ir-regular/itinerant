<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\StrategyStack;

class UserDefined
{
    /**
     * @var StrategyStack
     */
    private $stack;

    /**
     * @var array
     */
    private $strategy;

    public function __construct(
        StrategyStack $stack,
        $strategy,
        $args
    ) {
        $this->stack = $stack;
        $this->strategy = $this->fillPlaceholders($strategy, $args);
    }

    public function __invoke()
    {
        $this->stack->pop();
        $this->stack->push($this->strategy);

        return null; // always non-terminal
    }

    private function fillPlaceholders($strategy, $args)
    {
        // substitute numeric placeholders with the actual arguments
        // @TODO: yep, it's ugly, and it doesn't validate the index
        array_walk_recursive($strategy, function (&$value) use ($args) {
            if (is_numeric($value)) {
                $value = $args[(int) $value];
            }
        });

        return $strategy;
    }
}
