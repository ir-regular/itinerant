<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Fail
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
                    return null;
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

    public function __invoke(): NodeAdapterInterface
    {
        // ignore arguments and return terminating value 'fail'
        return self::fail();
    }
}
