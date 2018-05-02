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

## How to use it

### DSL

There are 7 inbuilt instructions that describe ways of operating on a node and its children.
An expression is an array which begins with an instruction, followed by zero or more expressions.

Instructions operating on a node:

- `id`: return current node
- `fail`: return `fail` node (fail)

Instructions operating on a node and two arguments (sub-expressions):

- `seq` (equivalent to short-circuit AND)
    1. apply first argument to node and check the result
        1. if the result is fail, then return fail
        2. otherwise, apply second argument to node and return the result
- `choice` (equivalent to short-circuit OR)
    1. apply first argument and check the result
        2. if the result is _not_ fail, return the initial result
        1. otherwise, apply second argument to node and return the result

Instructions operating on children of a node, and one argument (sub-expression):

- `all` (equivalent to EVERY)
    1. for each child of the node, apply the argument to the child and check the result
        1. if fail, return `fail` node (fail)
        2. otherwise, proceed to the next node
- `one` (equivalent to ANY/SOME)
    1. for each child of the node, apply the argument to the child and check the result
        1. if fail, proceed to next node
        2. otherwise, return the current node

Instructions operating on a single node that require a PHP callback and a fallback expression
(this is where you actually do something to the node):

- `adhoc`
    1. execute `isApplicable` callback on the node to check if the `action` callback can operate on it as well
        1. if yes, execute the `action` callback on the node
        2. if no, apply the fallback expression to the node
    2. return the result of either callback or fallback expression, as appropriate

### Extending the instruction set

You can register your own instructions by providing an instruction name and an expression (instruction definition).
Itinerant will apply the definition every time it encounters the name.

You are allowed to use the instruction name in instruction definition.

The definition can also contain placeholders '0', '1', ... which indicate places where Itinerant should insert
arguments when applying the instruction to a node. 

### Example: depth first

Example definition of depth-first traversal:

`td(X) := seq(X, all(td, X))`

That is, to apply expression `X` to a tree, proceed as follows:

1. apply expression X to the current node
    1. if the result is a fail, return `fail` (this is an equivalent of throwing an exception)
    2. otherwise:
        1. for all children of the current node, execute step 1

`X` is a placeholder for an argument. Argument value is provided when applying instruction `td` to a node.
The argument must be a valid expression. Generally, you can expect it to be an `adhoc` instruction that executes
a callback.

### Code example

```php
// root node of the tree
$root = ...;
// The root needs to be wrapped in a class implementing NodeAdapterInterface.
// Let's assume the tree consists of objects that have methods 'getChildren', 'setChildren' and 'getValue'
$node = new Accessor($root);

// callback to be executed on every node
$callback = function (NodeAdapterInterface $node): NodeAdapterInterface {
    printf('Node %s has %d children', $node->getName(), iterator_count($node->getChildren());
    return $node;
};

$itinerant = new Itinerant();
// Load some common traversal strategy definitions
$filepath = getcwd() . '/resources/common.itinerant';
Configurator::registerFromFile($itinerant, $filepath);

// You could also define how to perform depth-first traversal of all nodes by hand.
// Note '0' which is an argument placeholder: 'full_td' is an instruction with one argument.
// (Compare to how full_td definition looks like in resources/common.itinerant)
// $itinerant->register('full_td', ['seq', '0', ['all', ['full_td', '0']]]);

// Perform depth-first traversal, which will print out counts of all children
// In this instance, ['adhoc', 'id', $callback] is the argument value
$itinerant->apply(['full_td', ['adhoc', 'id', $callback]], $node);
```

### Difficulty level++

You can write your own implementations of `NodeAdapterInterface`.

A node can represent any piece of code that has dependencies on other nodes being executed/generated first. Since
`NodeAdapterInterface::getChildren()` returns an `Iterator`, you could implement this method as a generator and
construct the graph on the fly (look at `StringDefinition` and `StringExpression` classes for a basic example.)

All generic node adapters provided in this library expect their children to implement `NodeAdapterInterface`,
but make no further assumptions. You can mix-and-match implementations as needed (for example,
`StringDefinition` generates `StringExpression` children).

You can switch node adapters when executing your actions (for example, `ValidateInstructionDefinitionAction` re-wraps
the node value in a `Leaf` adapter.)

(Compare `Leaf` to `Iterator`.)

## Cool trivia

Internally, Itinerant converts recursion to iteration.

With the exception of `id` and `fail`, all base instructions work as coroutines that return continuations.
They implement `__invoke` magic method as a generator.

Itinerant is self-validating (see `ExpressionValidator`) and self-parsing (see `StringDefinition` and `StringExpression`).

All generic node adapters provided in this library clone rather than modify data, so you could say that Itinerant
operates on immutable data structures. (Unless you implement your own node adapter.)

## Source

The initial inspiration comes from the excellent article ["The Essence of Strategic Programming" by Ralf Lammel, Eelco Visser, Joost Visser](https://www.researchgate.net/publication/277289331_The_Essence_of_Strategic_Programming)
