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
    private $strategyIfInapplicable;
    private $action;
    /**
     * @var NodeAdapterInterface
     */
    private $node;

    public function __construct(
        StrategyStack $stack,
        NodeAdapterInterface $node,
        $strategyIfInapplicable,
        $action
    ) {
        $this->stack = $stack;
        $this->strategyIfInapplicable = $strategyIfInapplicable;
        $this->action = $action;
        $this->node = $node;
    }

    public function __invoke(NodeAdapterInterface $previousResult): ?NodeAdapterInterface
    {
        $applied = false;
        $res = null; // non-terminal by default

        if (is_callable($this->action)) {
            // strategy resolved to applied action; terminal unless null returned
            // @TODO: document this clearly somewhere
            $res = ($this->action)($this->node);
            $applied = ($res !== null);
        }

        if ($applied) {
            // @TODO: it would be better to check this elsewhere
            if (!($res instanceof NodeAdapterInterface)) {
                throw new \UnexpectedValueException('Adhoc callable result must be a NodeAdapterInterface');
            }
        } else {
            $this->stack->pop(); // remove self, fully resolved
            $this->stack->push($this->strategyIfInapplicable); // resolve strategy $s with $previousResult
        }

        return $res;
    }
}
