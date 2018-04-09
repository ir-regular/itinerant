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
     * @var NodeAdapterInterface[]
     */
    private $children;

    /**
     * @param resource $definition
     * @param null|string $peeked
     */
    public function __construct($definition, ?string $peeked = null)
    {
        $this->children = [];
        $this->children['declaration'] = new StringExpression($definition, $peeked);
        // need to do this separately since instruction uses declaration
        $this->children['instruction'] = $this->getInstruction($definition);
    }

    public function getNode()
    {
        $strategy = $this->children['declaration']->getValue();
        $instruction = $this->children['instruction']->getNode();

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

        while (($c = fgetc($definition)) !== false) {
            if ($this->isWordCharacter($c)) {
                break;
            }
        }

        if ($c !== false) {
            array_shift($symbols); // args
            $substitutions = array_flip($symbols);
            return new StringExpression($definition, $c, $substitutions);
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
}
