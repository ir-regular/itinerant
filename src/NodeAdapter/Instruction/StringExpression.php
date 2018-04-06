<?php

namespace JaneOlszewska\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class StringExpression implements NodeAdapterInterface
{
    /** @var string */
    private $name;

    /** @var \Iterator */
    private $children;

    /** @var string|bool */
    private $lastReadCharacter;

    /** @var \Ds\Set|array|null */
    private $knownSymbols;

    /**
     * @param resource $definition
     * @param null|string $peeked
     * @param \Ds\Set|array $knownSymbols
     */
    public function __construct($definition, ?string $peeked = null, $knownSymbols = null)
    {
        $this->knownSymbols = $knownSymbols;
        $this->name = $this->extractName($definition, $peeked);

        if ($this->lastReadCharacter == '(') {
            $this->children = $this->extractChildren($definition);
        }
    }

    public function getNode()
    {
        $instruction = [$this->getValue()];

        foreach ($this->getChildren() as $child) {
            $instruction[] = $child->getNode();
        }

        return $instruction;
    }

    public function getValue()
    {
        return $this->name;
    }

    public function getChildren(): \Iterator
    {
        if ($this->children) {
            yield from $this->children;
        }
    }

    public function setChildren(array $children = []): void
    {
        $this->children = new \ArrayIterator($children);
    }

    /**
     * @param resource $definition
     * @param string|null $peeked
     * @return string
     */
    private function extractName($definition, ?string $peeked = null): string
    {
        $name = $peeked;

        while (($c = fgetc($definition)) !== false && $this->isWordCharacter($c)) {
            $name .= $c;
        }

        $this->lastReadCharacter = $c;

        if (is_null($name)) {
            throw new \UnexpectedValueException("Invalid expression: name not found");
        }

        if ($this->knownSymbols && !$this->isKnownSymbol($this->knownSymbols, $name)) {
            throw new \UnexpectedValueException("Unknown symbol: {$name}");
        }

        return $name;
    }

    /**
     * @param resource $definition
     * @return \Iterator
     */
    private function extractChildren($definition): \Iterator
    {
        while ($c = fgetc($definition)) {
            if (')' == $c) {
                break;
            } elseif ($this->isWordCharacter($c)) {
                // this new object will advance $definition stream internally
                $child = new self($definition, $c, $this->knownSymbols);

                yield $child;

                // last child eats up the closing ')' so we need to check it to know we ended
                if ($child->lastReadCharacter == ')') {
                    break;
                }
            }
        }

        $this->lastReadCharacter = $c;
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
     * @param \Ds\Set|array $knownSymbols
     * @param string $string
     * @return bool
     */
    private function isKnownSymbol($knownSymbols, string $string): bool
    {
        if (is_array($knownSymbols)) {
            return in_array($string, $knownSymbols);

        } else {
            return $knownSymbols->contains($string);
        }
    }
}
