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
     * @var array|\Ds\Set|null
     */
    private $knownSymbols;

    /**
     * @var NodeAdapterInterface[]
     */
    private $children;

    /**
     * @param resource $definition
     * @param \Ds\Set|array|null $knownSymbols
     */
    public function __construct($definition, $knownSymbols = null)
    {
        $this->definition = $definition;
        $this->knownSymbols = $knownSymbols;

        $this->children = [];
        $this->children['declaration'] = new StringExpression($definition);
        // need to do this separately since instruction uses declaration
        $this->children['instruction'] = $this->getInstruction($definition);
    }

    public function getNode()
    {
        $declaration = $this->children['declaration']->getNode();
        $instruction = $this->children['instruction']->getNode();

        $strategy = array_shift($declaration);
        $args = array_map('array_pop', $declaration); // unwrap the rest
        $args = array_flip($args); // arg name => arg index

        // substitute arg names with numeric placeholders
        array_walk_recursive($instruction, function (&$value) use ($args) {
            if (isset($args[$value])) {
                $value = strval($args[$value]);
            }
        });

        return [$strategy, $instruction];
    }

    public function getValue()
    {
        return $this->children['declaration']->getValue();
    }

    public function getChildren(): \Iterator
    {
        yield from $this->children;
    }

    public function setChildren(array $children = []): void
    {
        // cannot amend children yet
        throw new \RuntimeException('Not implemented');
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
        }, $this->children['declaration']->getNode());

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

        throw new \UnderflowException("Definition {$symbols[0]} incomplete: body missing");
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
