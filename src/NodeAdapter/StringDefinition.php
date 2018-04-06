<?php

namespace JaneOlszewska\Itinerant\NodeAdapter;

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
     * @param resource $definition
     */
    public function __construct($definition)
    {
        $this->definition = $definition;

        $this->declaration = $this->getExpression($definition);
    }

    public function getNode()
    {
        return $this->definition;
    }

    public function getValue()
    {
        return $this->declaration->getValue();
    }

    public function getChildren(): \Iterator
    {
        yield from $this->declaration->getChildren();

        while (($c = fgetc($this->definition)) !== false) {
            if ($this->isWordCharacter($c)) {
                break;
            }
        }

        if ($c !== false) {
            yield $this->getExpression($this->definition, $c);
        }
    }

    public function setChildren(array $children = []): void
    {
        // cannot amend children
    }

    private function getExpression($definition, ?string $peeked = null): NodeAdapterInterface
    {
        return new class($definition, $peeked) implements NodeAdapterInterface
        {
            /** @var resource */
            private $definition;

            /** @var string */
            private $name;

            /** @var \Iterator */
            private $children;

            /** @var string */
            private $peeked;

            /**
             * @param resource $definition
             * @param null|string $peeked
             */
            public function __construct($definition, ?string $peeked = null)
            {
                $this->name = $peeked;

                while (($c = fgetc($definition)) !== false && $this->isWordCharacter($c)) {
                    $this->name .= $c;
                }

                $this->peeked = $c;

                if ($c == '(') {
                    $this->children = $this->match($definition);
                }
            }

            public function getNode()
            {
                return $this->definition;
            }

            public function getValue()
            {
                return $this->name;
            }

            public function getChildren(): \Iterator
            {
                yield from $this->children;
            }

            public function setChildren(array $children = []): void
            {
                // cannot amend children
            }

            /**
             * @param resource $definition
             * @return \Iterator
             */
            private function match($definition): \Iterator
            {
                while ($c = fgetc($definition)) {
                    if (')' == $c) {
                        break;
                    } elseif ($this->isWordCharacter($c)) {
                        // this new object will advance $definition stream internally
                        $child = new self($definition, $c);
                        yield $child;
                        if ($child->peeked == ')') {
                            break;
                        }
                        // child eats up the closing ')' so we need to check it to know we ended
                    }
                }

                $this->peeked = $c;
            }

            private function isWordCharacter($char)
            {
                return strpos(' (),=', $char) === false;
            }
        };
    }

    private function isWordCharacter($char)
    {
        return strpos(' (),=', $char) === false;
    }
}
