<?php

namespace JaneOlszewska\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * Parses a string to an Itinerant::registerStrategy() compliant input.
 *
 * Because of generators, this is somewhat fragile.
 * Do not attempt to getNode() and getChildren() on the same object (or vice versa).
 */
class StringDefinition implements NodeAdapterInterface
{
    /**
     * @var resource
     */
    private $definition;

    /**
     * @var NodeAdapterInterface
     */
    private $declaration;

    /**
     * @var array|\Ds\Set|null
     */
    private $knownSymbols;

    /**
     * @param resource $definition
     * @param \Ds\Set|array|null $knownSymbols
     */
    public function __construct($definition, $knownSymbols = null)
    {
        $this->definition = $definition;
        $this->declaration = new StringExpression($definition);
        $this->knownSymbols = $knownSymbols;
    }

    public function getNode()
    {
        return [$this->declaration->getValue(), $this->getInstruction($this->definition)->getNode()];
    }

    public function getValue()
    {
        return $this->declaration->getValue();
    }

    public function getChildren(): \Iterator
    {
        yield 'declaration' => $this->declaration;
        yield 'instruction' => $this->getInstruction($this->definition);
    }

    public function setChildren(array $children = []): void
    {
        // cannot amend children
    }

    /**
     * @param resource $definition
     * @return NodeAdapterInterface
     */
    private function getInstruction($definition): NodeAdapterInterface
    {
        // (this also forces the stream to be consumed up to the point where the instruction starts)

        $symbols = array_map(function ($s) {
            return is_array($s) ? array_pop($s) : $s;
        }, $this->declaration->getNode());

        if ($symbols) {
            $this->addKnownSymbols($symbols);
        }

        while (($c = fgetc($definition)) !== false) {
            if ($this->isWordCharacter($c)) {
                break;
            }
        }

        if ($c !== false) {
            return new StringExpression($definition, $c, $this->knownSymbols);
        }
    }

    /**
     * @param string $char
     * @return bool
     */
    private function isWordCharacter(string $char): bool
    {
        return ctype_alnum($char) || $char == '_';
    }

    /**
     * @param string[] $symbols
     * @return void
     */
    private function addKnownSymbols(array $symbols): void
    {
        if (is_array($this->knownSymbols)) {
            $this->knownSymbols = array_unique(array_merge($this->knownSymbols, $symbols));

        } elseif ($this->knownSymbols) {
            $this->knownSymbols = clone $this->knownSymbols;
            $this->knownSymbols->add(...$symbols);

        } elseif (class_exists('\Ds\Set')) {
            $this->knownSymbols = new \Ds\Set($symbols);

        } else {
            $this->knownSymbols = $symbols;
        }
    }
}
