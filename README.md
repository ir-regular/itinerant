# Itinerant

## What is it

Composable graph traversal library for PHP.

## Why use it

Itinerant allows you to separate traversal logic from acting on graph nodes.

That, in itself, isn't revolutionary. However, if you have to do more complicated graph traversal, and often, you will
find yourself handcrafting code with a lot of loops. Such code is usually bug-prone, messy, tedious to step through
and hard to understand.

Itinerant aims to solve the 'hard to understand' part by allowing you to define graph traversal algorithms in a way
that is:

- concise
- recursive
- functional

## Cool trivia about Itinerant

You define the algorithms recursively, but internally the library builds a symbol
stack and converts recursion to iteration. No more handcrafted loops.

A graph traversal algorithm definition can be imagined as a tree, with specific actions
constituting tree nodes. When you register a new graph traversal algorithm with
Itinerant, the library uses itself to walk the definition and validate it!

## How to use it

### Theory: 7 base components

There are 7 base actions you can compose your algorithm of.

Actions operating on a single node:

- `id`: return current node
- `fail`: return `fail` node (fail)

Actions operating on a single node and two sub-actions:

- `seq`
    1. execute first sub-action and check the result
        1. if fail, return `fail` node (fail)
        2. otherwise, execute second sub-action and return its result
- `choice`
    1. execute first sub-action and check the result
        1. if fail, execute second sub-action and return its result
        2. otherwise, return result of first sub-action

Actions operating on all children of a single node, and a sub-action:

- `all`
    1. for each child of the node, execute the sub-action and check the result
        1. if fail, return `fail` node (fail)
        2. otherwise, proceed to the next node
- `one`
    1. for each child of the node, execute the sub-action and check the result
        1. if fail, proceed to next node
        2. otherwise, return the current node

Actions operating on a single node, sub-action and callback (this is where you actually operate on the node):

- `adhoc`
    1. check if node is a suitable argument for the callback
        1. if yes, execute the callback on the node
        2. if no, execute the action on the node
    2. return the result of either action or callback, as appropriate

### Example: depth first

Example definition of depth-first traversal:

`td(A) := seq(A, all(td, A))`

That is, to apply an action `A` to a tree, proceed as follows:

1. execute action A on the current node
    1. if fail, return `fail` (this is an equivalent of throwing an exception and breaking out of the traversal)
    2. otherwise:
        1. for all children of the current node, execute step 1

`A` is a placeholder for an argument. Argument value is provided when executing
the action. The argument can consist of any one of the above seven actions,
or another composite action defined previously by the user. Generally, you can expect
it to be an `adhoc` action that executes a callback.

### Code example

Work in progress: this is currently functional, but 

```php
// root node of the tree
$root = ...;
// callback to be executed on every node
$callback = function ($node) {
    printf('Node %s has %d children', $node->getName(), count($node->getChildren());
    return $node;
};

// Something that implements ChildHandlerInterface
// ViaGetter means we're operating on nodes that implement getChildren and setChildren methods
$childHandler = new ViaGetter();

// Define a unique node representing the state of failure
$fail = ...;

$ts = new ValidatedTraversalStrategy($childHandler, $fail);

// Define how to perform depth-first traversal of all nodes
$ts->registerStrategy('full_td', ['seq', '0', ['all', ['full_td', '0']]], 1);

// Perform depth-first traversal
$result = $ts->apply(['full_td', ['adhoc', 'id', $callback]], $root);
```

## Source

The excellent article ["The Essence of Strategic Programming" by Ralf Lammel, Eelco Visser, Joost Visser](https://www.researchgate.net/publication/277289331_The_Essence_of_Strategic_Programming)
Go read that for theory and more examples.

That's where the original wording (strategy for the composable elements, action for `adhoc`)
comes from, by the way.

## TODO

- giant refactoring
- change terms used across the library (strategy and action)
- make it easier to generate strategy definitions
- is it necessary to instantiate `ValidatedTraversalStrategy`?
- should strategies be registered per-instance or in a static variable?
- how to provide a good number of preconfigured basic strategies?
