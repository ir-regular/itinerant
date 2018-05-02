<?php

namespace JaneOlszewska\Itinerant\Config;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * Parses a string into an Itinerant::register() compliant format.
 *
 * Because of generators, this is somewhat fragile.
 * Do not attempt to getNode() and getChildren() on the same object (or vice versa).
 *
 * @TODO rename class
 * @TODO move to Utils namespace
 */
class StringDefinition implements NodeAdapterInterface
{
    /**
     * @var NodeAdapterInterface[]
     */
    private $children;

    /**
     * @param resource $source
     * @param null|string $peeked
     */
    public function __construct($source, ?string $peeked = null)
    {
        $this->children = [];
        $this->children['declaration'] = new StringExpression($source, $peeked);

        // (the following also forces the stream to be consumed up to the point where definition starts)

        $symbols = array_map(function ($s) {
            return is_array($s) ? array_pop($s) : $s;
        }, $this->children['declaration']->getNode());

        $instructionName = array_shift($symbols);
        $substitutions = array_flip($symbols);

        $this->children['definition'] = $this->getDefinition($source, $instructionName, $substitutions);
    }

    public function getNode()
    {
        $instructionName = $this->children['declaration']->getValue();
        $definition = $this->children['definition']->getNode();

        return [$instructionName, $definition];
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
     * @param resource $source
     * @param string $instruction
     * @param array $substitutions
     * @return NodeAdapterInterface
     */
    private function getDefinition($source, string $instruction, array $substitutions): NodeAdapterInterface
    {
        while (($c = fgetc($source)) !== false) {
            if ($this->isWordCharacter($c)) {
                break;
            }
        }

        if ($c !== false) {
            return new StringExpression($source, $c, $substitutions);
        }

        throw new \UnderflowException("{$instruction} incomplete: definition missing");
    }

    /**
     * Because there's no ctype_* function corresponding to \w -.-
     *
     * @param string $char
     * @return bool
     */
    private function isWordCharacter(string $char): bool
    {
        return ctype_alnum($char) || $char == '_';
    }
}
