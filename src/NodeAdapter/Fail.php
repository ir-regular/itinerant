<?php

namespace IrRegular\Itinerant\NodeAdapter;

/**
 * Ladies and gentlemen, it's a singleton!
 * You are hereby authorised to hate me with the passion of a thousand suns.
 */
final class Fail implements NodeAdapterInterface
{
    /**
     * @var Fail
     */
    private static $fail;

    public static function fail(): Fail
    {
        if (!self::$fail) {
            self::$fail = new Fail();
        }

        return self::$fail;
    }

    private function __construct()
    {
    }

    public function getNode()
    {
        throw new \RuntimeException('FAIL');
    }

    public function getValue()
    {
        return 'FAIL';
    }

    public function getChildren(): \Iterator
    {
        yield from [];
    }

    public function setChildren(array $children = []): void
    {
        throw new \RuntimeException('FAIL');
    }
}
