<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\Action\ValidateTraversalStrategy;
use JaneOlszewska\Itinerant\Action\ValidateUserRegisteredTraversalStrategy;
use JaneOlszewska\Itinerant\ChildHandler\RestOfElements;

class ValidatedTraversalStrategy extends TraversalStrategy
{
    private const INBUILT_STRATEGIES = [
        self::ID,
        self::FAIL,
        self::SEQ,
        self::CHOICE,
        self::ALL,
        self::ONE,
        self::ADHOC,
    ];
    protected const STRATEGY_VALIDATE = 'validate';
    const STRATEGY_VALIDATE_REGISTERED = 'validate_registered';

    /** @var int[] */
    private $argCounts = [
        // Note that this array does not include non-public predefined strategies, which are therefore disallowed
        // by validation and made usable only internally
        self::ID => 0,
        self::FAIL => 0,
        self::SEQ => 2,
        self::CHOICE => 2,
        self::ALL => 1,
        self::ONE => 1,
        self::ADHOC => 2,
    ];

    /** @var ValidateTraversalStrategy */
    private $validatePreApplicationAction;

    /** @var ValidateUserRegisteredTraversalStrategy */
    private $validatePreRegistrationAction;

    /** @var TraversalStrategy */
    private $ts;

    private $validationFailValue = false;

    public function registerStrategy(string $key, array $expansion, int $argCount): void
    {
        $this->argCounts[$key] = $argCount;

        $expansion = $this->validateAndSanitiseBeforeRegistering($key, $expansion, $argCount);
        parent::registerStrategy($key, $expansion, $argCount);
    }

    /**
     * @param array|string $s
     * @param mixed $datum
     * @return mixed
     */
    public function apply($s, $datum)
    {
        $s = $this->validateAndSanitise($s);
        return parent::apply($s, $datum);
    }

    /**
     * @return TraversalStrategy
     */
    private function getInternalValidationTS()
    {
        if (!isset($this->ts)) {
            $childHandler = new RestOfElements();
            $this->ts = new TraversalStrategy($childHandler, $this->validationFailValue);
            $this->validatePreApplicationAction = new ValidateTraversalStrategy($childHandler);
            $this->validatePreRegistrationAction = new ValidateUserRegisteredTraversalStrategy($childHandler);

            $isNotCallableArray = function ($d) {
                return (is_array($d) && is_callable($d)) ? null : $d;
            };

            // register without validation to avoid infinite recursion
            $this->ts->registerStrategy(
                self::STRATEGY_VALIDATE,
                ['choice',
                    ['seq',
                        ['adhoc', ['fail'], $isNotCallableArray], // if doesn't fail, safe to nest further
                        ['seq', ['adhoc', ['fail'], $this->validatePreApplicationAction], ['all', [self::STRATEGY_VALIDATE]]], // top-down application of $a
                    ],
                    ['adhoc', ['fail'], $this->validatePreApplicationAction] // just validate without nesting
                ],
                0
            );

            // register without validation to avoid infinite recursion
            $this->ts->registerStrategy(
                self::STRATEGY_VALIDATE_REGISTERED,
                ['seq', ['adhoc', ['fail'], $this->validatePreRegistrationAction], ['all', [self::STRATEGY_VALIDATE_REGISTERED]]], // top-down application of $a
                0
            );
        }

        return $this->ts;
    }

    /**
     * Witness the ultimate coolness: TraversalStrategy is self-validating!
     *
     * @param array|string $strategy
     * @return array
     * @throws \InvalidArgumentException if $strategy is found invalid
     */
    private function validateAndSanitise($strategy)
    {
        $ts = $this->getInternalValidationTS();

        $this->validatePreApplicationAction->setStrategyArgumentCounts($this->argCounts);

        // apply without validation to avoid infinite recursion
        $result = $ts->apply([self::STRATEGY_VALIDATE], $strategy);

        if ($result === $this->validationFailValue) {
            $error = $this->validatePreApplicationAction->getLastError();
            throw new \InvalidArgumentException("Invalid argument structure for the strategy: {$error}");
        }

        return $result;
    }

    /**
     * Needs slightly expanded validation rules for registered strategies: they contain argument substitution markers.
     *
     * @param string $strategyKey
     * @param array $strategy
     * @param int $argCount
     */
    private function validateAndSanitiseBeforeRegistering(string $strategyKey, array $strategy, int $argCount)
    {
        if (in_array($strategyKey, self::INBUILT_STRATEGIES)) {
            throw new \InvalidArgumentException("Cannot overwrite inbuilt strategy key: {$strategyKey}");
        }

        $ts = $this->getInternalValidationTS();

        $this->validatePreRegistrationAction->setStrategyArgumentCounts(
            array_merge([$strategyKey => $argCount], $this->argCounts)
        );

        // apply without validation to avoid infinite recursion
        $result = $ts->apply([self::STRATEGY_VALIDATE_REGISTERED], $strategy);

        if ($result === $this->validationFailValue) {
            $error = $this->validatePreRegistrationAction->getLastError();
            throw new \InvalidArgumentException("Invalid argument structure for the strategy: {$error}");
        }

        return $result;
    }
}
