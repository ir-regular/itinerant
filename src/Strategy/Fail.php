<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Fail implements StrategyInterface
{
    /**
     * @var NodeAdapterInterface
     */
    static private $failValue;

    /**
     * Returns a singleton "FAIL" value that conforms to NodeAdapterInterface.
     *
     * @return NodeAdapterInterface
     */
    public static function fail(): NodeAdapterInterface
    {
        if (!self::$failValue) {
            self::$failValue = new class implements NodeAdapterInterface {
                public function &getNode()
                {
                    return null;
                }

                public function getValue()
                {
                    return 'FAIL'; // just so you can tell this is the node
                }

                public function getChildren(): \Iterator
                {
                    yield from [];
                }

                public function setChildren(array $children = []): void
                {
                    // no children
                }
            };
        }

        return self::$failValue;
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        // ignore arguments and return terminating value 'fail'
        yield self::fail();
    }
}
