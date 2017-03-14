<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal;

/**
 * Temporary, for typehinting
 */
interface Datum
{
    /**
     * @return Datum[]
     */
    public function getChildren(): ?array;

//    /**
//     * @return int
//     */
//    public function getChildCount(): int;
//
//    /**
//     * @param int $index 0-based
//     * @return Datum
//     * @throws \OutOfBoundsException when index out of bounds requested
//     */
//    public function getChild(int $index): Datum;
//
//    /**
//     * @param Datum $datum
//     * @param int|null $index 0-based, optional - if provided, overrides child, if not given, appends child
//     * @throws \OutOfBoundsException when index out of bounds requested
//     */
//    public function setChild(Datum $datum, ?int $index = null): void;

    /**
     * @param Datum[] $children
     */
    public function setChildren(array $children = []): void;
}
