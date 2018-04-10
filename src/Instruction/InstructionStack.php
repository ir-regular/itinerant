<?php

namespace JaneOlszewska\Itinerant\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class InstructionStack
{
    /**
     * @var array|\Ds\Stack
     */
    private $stack = [];

    /**
     * @var ExpressionResolver
     */
    private $resolver;

    public function __construct(ExpressionResolver $resolver)
    {
        if (class_exists('\Ds\Stack')) {
            $this->stack = new \Ds\Stack();
        } else {
            $this->stack = [];
        }

        $this->resolver = $resolver;
    }

    /**
     * @param array $expression
     * @param NodeAdapterInterface $node
     * @return NodeAdapterInterface
     */
    public function apply(array $expression, NodeAdapterInterface $node): NodeAdapterInterface
    {
        $this->push([$expression, $node]);
        $result = null;

        do {
            [$expression, $node] = $this->pop();

            if (is_array($expression)) {
                // speed things up by insta-resolving 'id' and 'fail' strategies
                if ($expression[0] == ExpressionResolver::ID) {
                    $result = $node;
                    continue;
                } elseif ($expression[0] == ExpressionResolver::FAIL) {
                    $result = Fail::fail();
                    continue;
                } else {
                    $instruction = $this->resolver->resolve($expression);
                    $continuation = $instruction->apply($node);
                    $result = $continuation->current();
                }
            } else {
                $continuation = $expression;
                // pass result of previous instruction to continuation
                $result = $continuation->send($result);
            }

            if (!($result instanceof NodeAdapterInterface)) {
                // instruction non-terminal: $result is an expression to execute on $node

                // preserve current state
                $this->push([$continuation, null]);
                // ...and queue up the expression for processing
                $this->push($result);
            }

            // else: instruction terminal: it transformed $node into $result
            // Now pass the result into the continuation lower on the stack
            // (see above for the branch that runs when !is_array($expression))

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
     * @param array $expression
     * @return void
     */
    private function push(array $expression): void
    {
        if (is_array($this->stack)) {
            $this->stack[] = $expression;
        } else {
            $this->stack->push($expression);
        }
    }
}
