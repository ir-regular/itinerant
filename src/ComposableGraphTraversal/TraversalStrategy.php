<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal;

/**
 * You may be wondering why all the methods specify return type ?Datum even though some of them explicitly return null.
 * This is because I *may* want to make this prettier and make things more generic. Someday.
 *
 * This will also be converted into duck typing soon (tm) because as nice as strict typing is, some of us
 * have to work with existing libraries that don't implement the Datum interface.
 */
class TraversalStrategy
{
    const ID = 'id';
    const FAIL = 'fail';
    const SEQ = 'seq';
    const CHOICE = 'choice';
    const ALL = 'all';
    const ONE = 'one';
    const ADHOC = 'adhoc';

    // intermediate stage, required due to passing of results
    private const CHOICE_INTERMEDIATE = 'choice-intermediate';
    private const SEQ_INTERMEDIATE = 'seq-intermediate';
    private const ALL_INTERMEDIATE = 'all-intermediate';
    private const ONE_INTERMEDIATE = 'one-intermediate';

    // todo: currently not in use; to be used for registering user-defined strategies
    private $strategies = [
//        self::ID => ['TraversalStrategy', 'id'],
//        self::FAIL => ['TraversalStrategy', 'fail'],
//        self::SEQ => ['TraversalStrategy', 'seq'],
//        self::CHOICE => ['TraversalStrategy', 'choice'],
//        self::ALL => ['TraversalStrategy', 'all'],
//        self::ONE => ['TraversalStrategy', 'one'],
//        self::ADHOC => ['TraversalStrategy', 'adhoc'],
    ];
    private $argCounts = [
        // tbd
    ];

    /** @var array */
    private $stack = [];

    /** @var int */
    private $last = -1;

    /** @var Datum */
    private static $fail;

    public static function getFail()
    {
        // todo: urk, do something else
        if (!self::$fail) {
            self::$fail = new class implements Datum
            {
                private $s = 'fail';
                public function getChildren(): ?array
                {
                    return null; // intentionally empty
                }

                public function setChildren(array $children = []): void
                {
                    // do nothing
                }
            };
        }

        return self::$fail;
    }

    /**
     * @param string $key
     * @param array $expansion
     * @param int $argCount
     */
    public function registerStrategy(string $key, array $expansion, int $argCount): void
    {
        // todo: validate $expansion contents - should only contain default and registered keys
        // (can also use $key which is just being registered, for recursion)
        $this->strategies[$key] = $expansion;
        $this->argCounts[$key] = $argCount;
    }

    public function apply(array $s, Datum $datum): Datum
    {
        // todo: verify that $s is well-structured

        $this->push($s, $datum);
        $currentDatum = $datum;

        do {
            $key = $this->getCurrentStratKey();

            // todo: so this could be simplified. Later. I'm in the dirty hacking mode.

            switch ($key) {
                case self::FAIL:
                    $result = $this->fail($currentDatum);
                    break;
                case self::ID:
                    $result = $this->id($currentDatum);
                    break;
                case self::SEQ:
                    $result = $this->seq($currentDatum);
                    break;
                case self::SEQ_INTERMEDIATE:
                    $result = $this->seqIntermediate($currentDatum);
                    break;
                case self::CHOICE:
                    $result = $this->choice($currentDatum);
                    break;
                case self::CHOICE_INTERMEDIATE:
                    $result = $this->choiceIntermediate($currentDatum);
                    break;
                case self::ALL:
                    $result = $this->all($currentDatum);
                    break;
                case self::ALL_INTERMEDIATE:
                    $result = $this->allIntermediate($currentDatum);
                    break;
                case self::ONE:
                    $result = $this->one($currentDatum);
                    break;
                case self::ONE_INTERMEDIATE:
                    $result = $this->oneIntermediate($currentDatum);
                    break;
                case self::ADHOC:
                    $result = $this->adhoc($currentDatum);
                    break;
                default:
                    $result = $this->userDefined($currentDatum);
            }

            if ($result === null) {
                // strategy non-terminal, continue applying it to the same datum
                continue;
            }

            // problem: when allowing result=null to indicate non-terminal strategy,
            // how to achieve the passing/preservation of result?

            // strategy terminal: $currentDatum transformed into $result
            // pass the result into the strategy lower on the stack
            $this->pop();
            $currentDatum = $result;

        } while ($this->stack);

        return $result;
    }

    private function push($strategy, ?Datum $datum, ?array $unprocessed = null, ?array $processed = null): void
    {
        $stratKey = $strategy[0];
        $stratArgs = $strategy[1] ?? [];

        // strat, datum, childrenUnprocessed, childrenProcessed
        $this->stack[] = [
            'strat' => [$stratKey, $stratArgs],
            'input' => [$datum, $unprocessed],
            'result' => [null, $processed]
        ];

        $this->last++;
    }

    // todo: I don't quite like how processed/unprocessed children are stored, but I don't want to think of it now

    private function getCurrentStratKey()
    {
        return $this->stack[$this->last]['strat'][0];
    }

    private function getOriginalDatum(): Datum
    {
        return $this->stack[$this->last]['input'][0];
    }

    /**
     * @return Datum[]
     */
    private function getUnprocessedChildren(): ?array
    {
        return $this->stack[$this->last]['input'][1];
    }

    /**
     * @return Datum[]
     */
    private function getProcessedChildren(): ?array
    {
        return $this->stack[$this->last]['result'][1];
    }

    private function pop()
    {
        $this->last--;
        return array_pop($this->stack);
    }

    private function getArguments()
    {
        return $this->stack[$this->last]['strat'][1];
    }

    private function getArg($index)
    {
        if (!array_key_exists($index, $this->stack[$this->last]['strat'][1])) {
            $strat = $this->getCurrentStratKey();
            $count = count($this->stack[$this->last]['strat'][1]);
            throw new \InvalidArgumentException(
                "Too few arguments supplied for strategy {$strat}: {$index}'th requested, {$count} available");
        }

        return $this->stack[$this->last]['strat'][1][$index];
    }

    private function id(Datum $previousResult): Datum
    {
        return $previousResult;
    }

    private function fail(Datum $previousResult): Datum
    {
        return self::$fail;
    }

    private function seq(Datum $previousResult): ?Datum
    {
        $s1 = $this->getArg(0);
        $s2 = $this->getArg(1);

        $this->pop();

        $this->push([self::SEQ_INTERMEDIATE, [$s2]], self::$fail);
        $this->push($s1, $previousResult);

        return null; // always non-terminal
    }

    private function seqIntermediate(Datum $previousResult): ?Datum
    {
        $res = $previousResult;

        if (self::$fail !== $previousResult) {
            $s2 = $this->getArg(0);
            $this->pop();
            $this->push($s2, $previousResult);
            $res = null;
        }

        return $res;
    }

    private function choice(Datum $previousResult): ?Datum
    {
        $s1 = $this->getArg(0);
        $s2 = $this->getArg(1);

        $this->pop(); // remove self

        $this->push($s2, self::$fail);
        $this->push([self::CHOICE_INTERMEDIATE], $previousResult);
        $this->push($s1, $previousResult);

        return null; // always non-terminal
    }

    private function choiceIntermediate(Datum $previousResult)
    {
        $res = $this->getOriginalDatum();

        if (self::$fail !== $previousResult) {
            $this->pop(); // pop self; $s2 will be auto-popped
            $res = $previousResult; // pass $s1 result
        }

        return $res;
    }

    private function all(Datum $previousResult): ?Datum
    {
        $s1 = $this->getArg(0);
        $res = $previousResult; // if $d has no children: return $d, strategy terminal independent of what $s1 actually is
        $unprocessed = $previousResult->getChildren();

        if ($unprocessed) {
            $this->pop();

            $this->push([self::ALL_INTERMEDIATE, [$s1]], $previousResult, $unprocessed, []);
            $this->push($s1, $unprocessed[0]);
            $this->push([self::ALL_INTERMEDIATE, [$s1]], $previousResult, $unprocessed, []); // only here to be immediately popped
            $res = $unprocessed[0];
        }

        return $res;
    }

    private function allIntermediate(Datum $previousResult): ?Datum
    {
        $res = $previousResult;

        if (self::$fail !== $previousResult) {
            $s1 = $this->getArg(0);
            $originalResult = $this->getOriginalDatum();

            // if the result of the last child resolution wasn't fail, continue
            $unprocessed = $this->getUnprocessedChildren();
            array_shift($unprocessed);

            $processed = $this->getProcessedChildren();
            $processed[] = $previousResult;

            if ($unprocessed) { // there's more to process
                $this->pop();

                $this->push([self::ALL_INTERMEDIATE, [$s1]], $originalResult, $unprocessed, $processed);
                $this->push($s1, $unprocessed[0]);
                $this->push([self::ALL_INTERMEDIATE, [$s1]], $originalResult, $unprocessed, $processed); // only here to be popped
                $res = $unprocessed[0];

            } else {
                $originalResult->setChildren($processed);
                $res = $originalResult;
            }
        }

        return $res;
    }

    private function one(Datum $previousResult): ?Datum
    {
        $s1 = $this->getArg(0);
        // if $d has no children: fail, strategy terminal independent of what $s1 actually is
        $res = self::$fail;
        $unprocessed = $previousResult->getChildren();

        if ($unprocessed) {
            $this->pop();

            // not interested in previously processed results: thus null
            $this->push([self::ONE_INTERMEDIATE, [$s1]], null, $unprocessed, null);
            $this->push($s1, $unprocessed[0]);
            // this one is just meant to get popped instantly
            $this->push([self::ONE_INTERMEDIATE, [$s1]], null, $unprocessed, null);
            $res = $unprocessed[0];
        }

        return $res;
    }

    private function oneIntermediate(Datum $previousResult): ?Datum
    {
        $res = $previousResult;

        if (self::$fail === $previousResult) {
            // if the result of the last child resolution was fail, need to try with the next one (if exists)
            $s1 = $this->getArg(0);

            $unprocessed = $this->getUnprocessedChildren();
            array_shift($unprocessed);

            if ($unprocessed) { // fail, but there's more to process
                $this->pop();

                // not interested in previously processed results: thus null
                $this->push([self::ONE_INTERMEDIATE, [$s1]], null, $unprocessed, null);
                $this->push($s1, $unprocessed[0]);
                // this one is just meant to get popped instantly
                $this->push([self::ONE_INTERMEDIATE, [$s1]], null, $unprocessed, null);
                $res = $unprocessed[0];
            }

            // else: well we processed everything and nothing succeeded so: FAIL ($res === $previousResult === FAIL)
        }

        return $res;
    }

    private function adhoc(Datum $previousResult): ?Datum
    {
        $s = $this->getArg(0);
        /** @var Action $a */
        $a = $this->getArg(1);

        if ($a->isApplicableTo($previousResult)) {
            $res = $a->applyTo($previousResult); // strategy resolved to applied action; terminal

        } else {
            $this->pop(); // remove self, fully resolved
            $this->push($s, $previousResult); // resolve strategy $s with $d
            $res = null; // non-terminal
        }

        return $res;
    }

    private function userDefined(Datum $previousResult): ?Datum
    {
        $key = $this->getCurrentStratKey();

        if (!isset($this->strategies[$key])) {
            throw new \InvalidArgumentException("Strategy {$key} not registered");
        }

        $originalDatum = $this->getOriginalDatum();
        $args = $this->getArguments();

        if (count($args) !== $this->argCounts[$key]) {
            throw new \LengthException("Wrong number of arguments provided for user defined strategy {$key}");
        }

        $strategy = $this->strategies[$key];

        // substitute numeric placeholders with the actual arguments
        array_walk_recursive($strategy, function (&$value) use ($args) {
            if (is_numeric($value)) {
                $value = $args[(int)$value];
            }
        });

        $this->pop();
        $this->push($strategy, $originalDatum);

        return null; // always non-terminal
    }
}
