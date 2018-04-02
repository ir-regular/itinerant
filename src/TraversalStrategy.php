<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Strategy\Adhoc;
use JaneOlszewska\Itinerant\Strategy\All;
use JaneOlszewska\Itinerant\Strategy\Choice;
use JaneOlszewska\Itinerant\Strategy\Fail;
use JaneOlszewska\Itinerant\Strategy\Id;
use JaneOlszewska\Itinerant\Strategy\One;
use JaneOlszewska\Itinerant\Strategy\Seq;
use JaneOlszewska\Itinerant\Strategy\UserDefined;

class TraversalStrategy
{
    const ID = 'id';
    const FAIL = 'fail';
    const SEQ = 'seq';
    const CHOICE = 'choice';
    const ALL = 'all';
    const ONE = 'one';
    const ADHOC = 'adhoc';

    /** @var array */
    private $strategies = [];

    /** @var StrategyStack */
    private $stack;

    /** @var NodeAdapterInterface */
    private $fail;

    /**
     * @param mixed $fail A representation of "fail", valid for whatever nodes/adhoc methods will be used
     */
    public function __construct(NodeAdapterInterface $fail)
    {
        $this->stack = new StrategyStack();
        $this->fail = $fail;
    }

    /**
     * @return NodeAdapterInterface
     */
    public function getFail(): NodeAdapterInterface
    {
        return $this->fail;
    }

    /**
     * @param string $key
     * @param array $expansion
     * @param int $argCount
     */
    public function registerStrategy(string $key, array $expansion, int $argCount): void
    {
        // @TODO: remove $argCount, have ValidatedTraversalStrategy figure it out on its own
        $this->strategies[$key] = $expansion;
    }

    public function isStrategyRegistered(string $key): bool
    {
        return isset($this->strategies[$key]);
    }

    private function getStrategy($key, $datum, $args)
    {
        switch ($key) {
            case self::ID:
                return new Id($datum);
            case self::FAIL:
                return new Fail($this->fail);
            case self::SEQ:
                return new Seq($this->stack, $this->fail, $datum, ...$args);
            case self::CHOICE:
                return new Choice($this->stack, $this->fail, $datum, ...$args);
            case self::ALL:
                return new All($this->stack, $this->fail, $datum, ...$args);
            case self::ONE:
                return new One($this->stack, $this->fail, $datum, ...$args);
            case self::ADHOC:
                return new Adhoc($this->stack, $datum, ...$args);
            default:
                return $this->strategies[$key]
                    ? new UserDefined($this->stack, $this->strategies[$key], $args)
                    : null;
        }
    }

    /**
     * @param array $s
     * @param NodeAdapterInterface $datum
     * @return NodeAdapterInterface|null
     */
    public function apply($s, NodeAdapterInterface $datum): ?NodeAdapterInterface
    {
        $this->stack->push($s);
        $currentDatum = $datum;

        do {
            $strategy = $this->stack->getCurrentStrat();
            $args = $this->stack->getCurrentStratArguments();

            if (is_string($strategy)) {
                if (!$strategy = $this->getStrategy($strategy, $currentDatum, $args)) {
                    /*
                     * That means null/NOP.
                     *
                     * Including this just in case there is some logic error in the library.
                     *
                     * In actual use null is only pushed as a filler, to be immediately popped in the last lines of
                     * the do...while loop when it is discovered $result is not null - see all/one implementations.
                     */
                    throw new \DomainException('Logic error: null/NOP should never be evaluated');
                }
            }

            $result = $strategy($currentDatum);

            if ($result === null) {
                // strategy non-terminal, continue applying it to the same datum
                continue;
            }


            // @TODO:
            // have a proper method on strategies that indicates whether they are terminal or not
            // and/or figure out how to better return results
            //
            // because I hate how they currently store the arguments and the structure being worked upon
            // (see stack, urgh) and it should simply have a pointer to the current node
            // (maybe a stack of pointers)
            //
            // and children should be coming from an immutable iterator of some sort
            // (gather the results separately)



            // problem: when allowing result=null to indicate non-terminal strategy,
            // how to achieve the passing/preservation of result?

            // strategy terminal: $currentDatum transformed into $result
            // pass the result into the strategy lower on the stack
            $this->stack->pop();
            $currentDatum = $result;
        } while (!$this->stack->isEmpty());

        return $result;
    }
}
