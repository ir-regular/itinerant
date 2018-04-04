<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\Action\SanitiseAppliedAction;
use JaneOlszewska\Itinerant\Action\SanitiseRegisteredAction;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\RestOfElements;
use JaneOlszewska\Itinerant\Strategy\Fail;
use JaneOlszewska\Itinerant\Strategy\StrategyResolver;

class Itinerant
{
    private const SANITISE_APPLIED = 'sanitise_applied';
    private const SANITISE_REGISTERED = 'sanitise_registered';

    /** @var int[] */
    private $argCounts = [
        StrategyResolver::ID => 0,
        StrategyResolver::FAIL => 0,
        StrategyResolver::SEQ => 2,
        StrategyResolver::CHOICE => 2,
        StrategyResolver::ALL => 1,
        StrategyResolver::ONE => 1,
        StrategyResolver::ADHOC => 2,
        self::SANITISE_APPLIED => 0,
        self::SANITISE_REGISTERED => 0
    ];

    /** @var SanitiseAppliedAction */
    private $sanitiseAppliedAction;

    /** @var SanitiseRegisteredAction */
    private $sanitiseRegisteredAction;

    /** @var StrategyResolver */
    private $resolver;

    /** @var StrategyStack */
    private $stack;

    public function __construct()
    {
        $this->resolver = new StrategyResolver();
        $this->stack = new StrategyStack($this->resolver);

        $this->registerValidationStrategies($this->resolver);
    }

    /**
     * @param string $key
     * @param array $expansion
     * @param int $argCount
     * @return void
     */
    public function registerStrategy(string $key, array $expansion, int $argCount): void
    {
        $expansion = $this->sanitiseRegistered($key, $expansion, $argCount);

        $this->resolver->registerStrategy($key, $expansion);

        $this->argCounts[$key] = $argCount;

        $this->sanitiseAppliedAction->setStrategyArgumentCounts($this->argCounts);
        $this->sanitiseRegisteredAction->setStrategyArgumentCounts($this->argCounts);
    }

    /**
     * @param array|string $s
     * @param NodeAdapterInterface $datum
     * @return NodeAdapterInterface
     */
    public function apply($s, NodeAdapterInterface $datum): NodeAdapterInterface
    {
        $s = $this->sanitiseApplied($s);

        return $this->stack->apply($s, $datum);
    }

    private function registerValidationStrategies(StrategyResolver $resolver)
    {
        $this->sanitiseAppliedAction = new SanitiseAppliedAction();
        $this->sanitiseRegisteredAction = new SanitiseRegisteredAction();

        // Check for whether we encountered an adhoc action, callback formatted like an array
        $isNotCallableArray = function ($d) {
            return (is_array($d) && is_callable($d)) ? null : $d;
        };

        // register without validation to avoid infinite recursion
        $resolver->registerStrategy(
            self::SANITISE_APPLIED,
            ['choice',
                ['seq',
                    // if doesn't fail, safe to nest further
                    ['adhoc', ['fail'], $isNotCallableArray],
                    // top-down application of $this->sanitiseAppliedAction
                    ['seq', ['adhoc', ['fail'], $this->sanitiseAppliedAction], ['all', [self::SANITISE_APPLIED]]],
                ],
                // just validate without nesting
                ['adhoc', ['fail'], $this->sanitiseAppliedAction]
            ]
        );

        // register without validation to avoid infinite recursion
        $resolver->registerStrategy(
            self::SANITISE_REGISTERED,
            // top-down application of $this->sanitiseRegisteredAction
            ['seq', ['adhoc', ['fail'], $this->sanitiseRegisteredAction], ['all', [self::SANITISE_REGISTERED]]]
        );

        $this->argCounts[self::SANITISE_APPLIED] = 0;
        $this->argCounts[self::SANITISE_REGISTERED] = 0;
    }

    /**
     * Witness the ultimate coolness: TraversalStrategy is self-validating!
     *
     * @param array|string $strategy
     * @return array
     * @throws \InvalidArgumentException if $strategy is found invalid
     */
    private function sanitiseApplied($strategy)
    {
        $this->sanitiseAppliedAction->setStrategyArgumentCounts($this->argCounts);

        // apply without validation to avoid infinite recursion
        $result = $this->stack->apply([self::SANITISE_APPLIED], new RestOfElements($strategy));

        if (Fail::fail() === $result) {
            $error = $this->sanitiseAppliedAction->getLastError();
            throw new \InvalidArgumentException("Invalid argument structure for the strategy: {$error}");
        }

        $result = $result->getNode();

        return $result;
    }

    /**
     * Needs slightly expanded validation rules for registered strategies: they contain argument substitution markers.
     *
     * @param string $strategyKey
     * @param array $strategy
     * @param int $argCount
     * @return array
     */
    private function sanitiseRegistered(string $strategyKey, array $strategy, int $argCount)
    {
        if (array_key_exists($strategyKey, $this->argCounts)) {
            throw new \InvalidArgumentException("Cannot overwrite registered strategy key: {$strategyKey}");
        }

        $this->sanitiseRegisteredAction->setStrategyArgumentCounts(
            array_merge([$strategyKey => $argCount], $this->argCounts)
        );

        // apply without validation to avoid infinite recursion
        $result = $this->stack->apply([self::SANITISE_REGISTERED], new RestOfElements($strategy));

        if (Fail::fail() === $result) {
            $error = $this->sanitiseRegisteredAction->getLastError();
            throw new \InvalidArgumentException("Invalid argument structure for the strategy: {$error}");
        }

        $result = $result->getNode();

        return $result;
    }
}
