<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Strategy\StrategyResolver;

class StrategyStack
{
    /**
     * @var array|\Ds\Stack
     */
    private $stack = [];

    /**
     * @var StrategyResolver
     */
    private $resolver;

    public function __construct(StrategyResolver $resolver)
    {
        if (class_exists('\Ds\Stack')) {
            $this->stack = new \Ds\Stack();
        } else {
            $this->stack = [];
        }

        $this->resolver = $resolver;
    }

    /**
     * @param array $strategy
     * @param NodeAdapterInterface $node
     * @return NodeAdapterInterface
     */
    public function apply(array $strategy, NodeAdapterInterface $node): NodeAdapterInterface
    {
        $this->push([$strategy, $node]);
        $result = null;

        do {
            [$strategy, $node] = $this->pop();

            if (is_array($strategy)) {
                // speed things up by insta-resolving 'id' and 'fail' strategies
                if ($strategy[0] == StrategyResolver::ID) {
                    $result = $node;
                    continue;
                } elseif ($strategy[0] == StrategyResolver::FAIL) {
                    $result = Fail::fail();
                    continue;
                } else {
                    $strategy = $this->resolver->resolve($strategy);
                    $continuation = $strategy->apply($node);
                    $result = $continuation->current();
                }
            } else {
                $continuation = $strategy;
                // apply continuation to result of previous strategy
                $result = $continuation->send($result);
            }

            if (!($result instanceof NodeAdapterInterface)) {
                // strategy non-terminal, preserve current state
                $this->push([$continuation, null]);
                // ...and queue up a new instruction
                $this->push($result);
            }

            // else: strategy terminal. $currentDatum transformed into $result
            // pass the result into the strategy lower on the stack
        } while (!$this->isEmpty());

        return $result;
    }

    private function isEmpty(): bool
    {
        if (is_array($this->stack)) {
            return empty($this->stack);
        } else {
            return $this->stack->isEmpty();
        }
    }

    private function pop(): array
    {
        if (is_array($this->stack)) {
            return array_pop($this->stack);
        } else {
            return $this->stack->pop();
        }
    }

    /**
     * @param array $strategy
     * @return void
     */
    private function push(array $strategy): void
    {
        if (is_array($this->stack)) {
            $this->stack[] = $strategy;
        } else {
            $this->stack->push($strategy);
        }
    }
}
