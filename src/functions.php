<?php

declare(strict_types=1);

namespace Fnc;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use RuntimeException;
use stdClass;

use function array_column;
use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_shift;
use function array_slice;
use function array_sum;
use function array_values;
use function clamp as phpClamp;
use function count;
use function explode;
use function fdiv;
use function func_get_args;
use function get_object_vars;
use function implode;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_nan;
use function is_numeric;
use function is_object;
use function is_string;
use function max as phpMax;
use function min as phpMin;
use function spl_object_id;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;
use function usort;

use const ARRAY_FILTER_USE_BOTH;

// phpcs:disable BrainbitsCodingStandard.Exceptions.GlobalException.GlobalException
// phpcs:disable SlevomatCodingStandard.Commenting.ForbiddenAnnotations.AnnotationForbidden
// phpcs:disable Squiz.Functions.GlobalFunction.Found

/**
 * Adds two numbers. `null` counts as 0.
 *
 * @category Math
 * @example  add(2, 3); // 5
 * @example  map(add(1, ?), [1, 2]); // [2, 3]
 */
function add(mixed $a, mixed $b): int|float
{
    return ($a ?? 0) + ($b ?? 0);
}

/**
 * Returns `true` if all elements of the list match the predicate, `false` if there are any that don't. Stops at the
 * first element that doesn't match.
 *
 * @param callable(mixed): mixed $predicate
 * @param array<mixed>           $list
 *
 * @category List
 */
function all(callable $predicate, array $list): bool
{
    foreach ($list as $item) {
        if (!$predicate($item)) {
            return false;
        }
    }

    return true;
}

/**
 * Takes a list of predicates and returns a predicate that is satisfied if all of them are satisfied by the given
 * arguments. Stops at the first predicate that isn't satisfied.
 *
 * @param array<callable> $predicates
 *
 * @return Closure(mixed...): bool
 *
 * @category Logic
 */
function allPass(array $predicates): Closure
{
    return static function (mixed ...$args) use ($predicates): bool {
        foreach ($predicates as $predicate) {
            if (!$predicate(...$args)) {
                return false;
            }
        }

        return true;
    };
}

/**
 * Returns a function that always returns the given value, whatever arguments it gets. Objects are returned as the
 * same instance.
 *
 * @param T $value
 *
 * @return Closure(mixed...): T
 *
 * @template T
 * @category Function
 */
function always(mixed $value): Closure
{
    return static fn (mixed ...$args) => $value;
}

/**
 * Returns `true` if at least one of the elements of the list matches the predicate, `false` otherwise. Stops at the
 * first match.
 *
 * @param callable(mixed): mixed $predicate
 * @param array<mixed>           $list
 *
 * @category List
 */
function any(callable $predicate, array $list): bool
{
    foreach ($list as $item) {
        if ($predicate($item)) {
            return true;
        }
    }

    return false;
}

/**
 * Takes a list of predicates and returns a predicate that is satisfied if at least one of them is satisfied by the
 * given arguments. Stops at the first predicate that is satisfied.
 *
 * @param array<callable> $predicates
 *
 * @return Closure(mixed...): bool
 *
 * @category Logic
 */
function anyPass(array $predicates): Closure
{
    return static function (mixed ...$args) use ($predicates): bool {
        foreach ($predicates as $predicate) {
            if ($predicate(...$args)) {
                return true;
            }
        }

        return false;
    };
}

/**
 * Calls the function with the elements of the list as arguments. String keys become named arguments.
 *
 * @param array<mixed> $args
 *
 * @category Function
 * @example  apply(max(...), [1, 5, 3]); // 5
 */
function apply(callable $fn, array $args): mixed
{
    return $fn(...$args);
}

/**
 * Returns a function that builds an array shaped like the spec: each callable in the spec is called with the
 * arguments, nested arrays are nested specs. The function requires as many arguments as the callable with the
 * most required parameters.
 *
 * @param array<mixed> $spec
 *
 * @return Closure(mixed...): array<mixed>
 *
 * @category Function
 * @example  applySpec(['name' => prop('name', ?), 'city' => path(['address', 'city'], ?)]);
 */
function applySpec(array $spec): Closure
{
    $spec = array_map(static fn ($value) => is_array($value) ? applySpec($value) : $value, $spec);

    $arity = 0;
    foreach ($spec as $fn) {
        $arity = phpMax($arity, _getArity($fn));
    }

    return _arity(
        $arity,
        static fn (mixed ...$args) => array_map(static fn (callable $fn) => $fn(...$args), $spec),
    );
}

/**
 * Returns a comparator for `sortWith()` that compares two values by the result of the function, ascending, with
 * `<=>`. Note that `<=>` compares numeric strings numerically (`'9999' < '10000'`); use
 * `ascendWith(strcmp(...), $fn)` for byte-wise string order.
 *
 * @return Closure(mixed, mixed): int
 *
 * @category Function
 * @example  sortWith([ascend(prop('carhouse_id', ?)), descend(prop('date', ?))], $records);
 */
function ascend(callable $fn): Closure
{
    return static fn (mixed $a, mixed $b): int => $fn($a) <=> $fn($b);
}

/**
 * Like `ascend()`, but compares the results of the function with the given comparison function, e.g. `strcmp(...)`
 * for byte-wise string order or `strnatcasecmp(...)`. These require strings; for values that may be ints, use
 * `static fn ($a, $b) => strcmp((string) $a, (string) $b)`. The comparison function may return any number or a
 * bool, like for `usort()`.
 *
 * @return Closure(mixed, mixed): int
 *
 * @category Function
 * @example  sortWith([ascendWith(strcmp(...), prop('carhouse_id', ?))], $records);
 */
function ascendWith(callable $compare, callable $fn): Closure
{
    // normalise floats and bools returned by the comparison function, as usort() accepts them too
    return static fn (mixed $a, mixed $b): int => $compare($fn($a), $fn($b)) <=> 0;
}

/**
 * Returns a copy of the array with the key set to the value.
 *
 * @param array<mixed> $array
 *
 * @return array<mixed>
 *
 * @category Object
 * @example  assoc('b', 2, ['a' => 1]); // ['a' => 1, 'b' => 2]
 */
function assoc(string|int $key, mixed $value, array $array): array
{
    $array[$key] = $value;

    return $array;
}

/**
 * Returns a copy of the array with the nested key set to the value. Missing levels are created as arrays, non
 * array values on the way are replaced.
 *
 * @param array<string|int> $path
 *
 * @category Object
 * @example  assocPath(['a', 'b'], 1, []); // ['a' => ['b' => 1]]
 */
function assocPath(array $path, mixed $value, mixed $array): mixed
{
    if ($path === []) {
        return $value;
    }

    $key = array_shift($path);
    $array = is_array($array) ? $array : [];
    $array[$key] = assocPath($path, $value, $array[$key] ?? null);

    return $array;
}

/**
 * Returns a predicate that is satisfied if both predicates are satisfied. The second predicate is only called if
 * the first one is satisfied.
 *
 * @return Closure(mixed...): bool
 *
 * @category Logic
 */
function both(callable $f, callable $g): Closure
{
    return static fn (mixed ...$args): bool => $f(...$args) && $g(...$args);
}

/**
 * Maps each element to a list and concatenates the results (flatMap). Returns a list.
 *
 * @param callable(mixed): iterable<mixed> $fn
 * @param array<mixed>                     $list
 *
 * @return list<mixed>
 *
 * @category List
 * @example  chain(fn ($n) => [$n, $n], [1, 2]); // [1, 1, 2, 2]
 */
function chain(callable $fn, array $list): array
{
    $result = [];
    foreach ($list as $value) {
        foreach ($fn($value) as $item) {
            $result[] = $item;
        }
    }

    return $result;
}

/**
 * Restricts the value to the range from `$min` to `$max`, using PHP's `clamp()`: works with anything comparable,
 * e.g. numbers or `Time\Duration`. Throws a `ValueError` if `$min` is greater than `$max`.
 *
 * @param T $min
 * @param T $max
 * @param T $value
 *
 * @return T
 *
 * @template T
 * @category Relation
 * @example  clamp(1, 10, 15); // 10
 * @example  map(clamp(0, 100, ?), [-5, 50, 150]); // [0, 50, 100]
 */
function clamp(mixed $min, mixed $max, mixed $value): mixed
{
    return phpClamp($value, $min, $max);
}

/**
 * Returns a function that returns the negated result of the given function.
 *
 * @return Closure(mixed...): bool
 *
 * @category Function
 */
function complement(callable $fn): Closure
{
    return static fn (mixed ...$args): bool => !$fn(...$args);
}

/**
 * Performs right-to-left function composition. The rightmost function may have any arity; the remaining functions
 * must be unary. Throws InvalidArgumentException without functions.
 *
 * @category Function
 */
function compose(callable ...$fns): Closure
{
    if ($fns === []) {
        throw new InvalidArgumentException('compose requires at least one argument');
    }

    return pipe(...array_reverse($fns));
}

/**
 * Concatenates two lists or two strings. Lists are treated as lists: the result contains the values of `$a`
 * followed by the values of `$b`, keys are not preserved. Use `merge()` for arrays with string keys.
 *
 * @param array<mixed>|string $a
 * @param array<mixed>|string $b
 *
 * @return list<mixed>|string
 *
 * @category List
 * @example  concat([1, 2], [3]); // [1, 2, 3]
 * @example  concat('ab', 'cd'); // 'abcd'
 */
function concat(array|string $a, array|string $b): array|string
{
    if (is_string($a) && is_string($b)) {
        return $a . $b;
    }

    if (is_array($a) && is_array($b)) {
        return [...array_values($a), ...array_values($b)];
    }

    throw new InvalidArgumentException('concat expects two arrays or two strings');
}

/**
 * Takes a list of [predicate, transformer] pairs and returns a function that calls the transformer of the first
 * pair whose predicate is satisfied. Returns null if no predicate matches; use `always(true)` as the last
 * predicate for a default.
 *
 * @param array<array{callable, callable}> $pairs
 *
 * @return Closure(mixed...): mixed
 *
 * @category Logic
 */
function cond(array $pairs): Closure
{
    return static function (mixed ...$args) use ($pairs) {
        foreach ($pairs as [$predicate, $transformer]) {
            if ($predicate(...$args)) {
                return $transformer(...$args);
            }
        }

        return null;
    };
}

/**
 * Returns a function that calls each of the branch functions with its arguments and passes their results to the
 * converging function.
 *
 * @param array<callable> $branches
 *
 * @return Closure(mixed...): mixed
 *
 * @category Function
 * @example  converge(divide(...), [sum(...), count(...)]); // average
 */
function converge(callable $after, array $branches): Closure
{
    return static fn (mixed ...$args) => $after(
        ...array_map(static fn (callable $branch) => $branch(...$args), $branches),
    );
}

/**
 * Decrements a number by 1.
 *
 * @category Math
 */
function dec(mixed $value): int|float
{
    return $value - 1;
}

/**
 * Returns the value, or the default if the value is `null` or `NAN`.
 *
 * @param D $default
 * @param T $value
 *
 * @return T|D
 *
 * @template D
 * @template T
 * @category Logic
 * @example  defaultTo(42, null); // 42
 */
function defaultTo(mixed $default, mixed $value): mixed
{
    if ($value === null || (is_float($value) && is_nan($value))) {
        return $default;
    }

    return $value;
}

/**
 * Returns a comparator for `sortWith()` that compares two values by the result of the function, descending.
 *
 * @return Closure(mixed, mixed): int
 *
 * @category Function
 */
function descend(callable $fn): Closure
{
    return static fn (mixed $a, mixed $b): int => $fn($b) <=> $fn($a);
}

/**
 * Like `descend()`, but compares the results of the function with the given comparison function, e.g.
 * `strcmp(...)`.
 *
 * @return Closure(mixed, mixed): int
 *
 * @category Function
 */
function descendWith(callable $compare, callable $fn): Closure
{
    return static fn (mixed $a, mixed $b): int => $compare($fn($b), $fn($a)) <=> 0;
}

/**
 * Divides two numbers. Equivalent to `$a / $b`, like Ramda: `null`, `false` and `''` count as 0, and dividing
 * by zero (also `'0'`) returns INF, -INF or NAN instead of throwing.
 *
 * @category Math
 */
function divide(mixed $a, mixed $b): int|float
{
    $orZero = static fn ($value) => $value === null || $value === false || $value === '' ? 0 : $value;
    $a = $orZero($a);
    $b = $orZero($b);

    if (is_numeric($a) && is_numeric($b) && (float) $b === 0.0) {
        return fdiv((float) $a, (float) $b);
    }

    return $a / $b;
}

/**
 * Removes the first `$count` elements of a list (like `array_slice()`: string keys are kept, numeric keys
 * renumbered) or characters of a string.
 *
 * @param array<mixed>|string $list
 *
 * @return array<mixed>|string
 *
 * @category List
 */
function drop(int $count, array|string $list): array|string
{
    if (is_array($list)) {
        return array_slice($list, $count);
    }

    return substr($list, $count);
}

/**
 * Returns a predicate that is satisfied if at least one of the predicates is satisfied. The second predicate is
 * only called if the first one isn't satisfied.
 *
 * @return Closure(mixed...): bool
 *
 * @category Logic
 */
function either(callable $f, callable $g): Closure
{
    return static fn (mixed ...$args): bool => $f(...$args) || $g(...$args);
}

/**
 * Returns the empty value of its argument's type.
 *
 * @category Function
 */
function emptyVal(mixed $value): mixed
{
    if (is_string($value)) {
        return '';
    }

    if (is_array($value)) {
        return [];
    }

    if (is_object($value) && is_callable($value)) {
        return static function (): void {
        };
    }

    if (is_object($value)) {
        return new stdClass();
    }

    if (is_int($value)) {
        return 0;
    }

    if (is_float($value)) {
        return 0.0;
    }

    if (is_bool($value)) {
        return false;
    }

    return null;
}

/**
 * Deep equality: scalars are compared strictly (unlike in Ramda, `1` and `1.0` differ, as PHP has separate int and
 * float types), `NAN` equals `NAN` like in Ramda, arrays by key (list order matters, order of string keys
 * doesn't), objects only if they are of the same class and all their properties are equal. Closures are
 * compared by identity. Cyclic object graphs are supported.
 *
 * @category Relation
 */
function equals(mixed $a, mixed $b): bool
{
    $compare = static function ($actual, $expected, array &$seen) use (&$compare): bool {
        if ($actual === $expected) {
            return true;
        }

        if (is_float($actual) && is_float($expected) && is_nan($actual) && is_nan($expected)) {
            return true;
        }

        if (is_object($actual) || is_object($expected)) {
            if (!is_object($actual) || !is_object($expected)) {
                return false;
            }

            if ($actual::class !== $expected::class || $actual instanceof Closure) {
                return false;
            }

            // a pair that is already being compared further up is assumed equal, which ends cycles
            $pair = spl_object_id($actual) . ':' . spl_object_id($expected);
            if (isset($seen[$pair])) {
                return true;
            }

            $seen[$pair] = true;

            $actual = (array) $actual;
            $expected = (array) $expected;
        }

        if (!is_array($actual) || !is_array($expected)) {
            return false;
        }

        if (count($actual) !== count($expected)) {
            return false;
        }

        foreach ($actual as $key => $value) {
            if (!array_key_exists($key, $expected) || !$compare($value, $expected[$key], $seen)) {
                return false;
            }
        }

        return true;
    };

    $seen = [];

    return $compare($a, $b, $seen);
}

/**
 * Returns a copy of the array with the transformations applied to the matching keys. A nested array of
 * transformations is applied to the nested array. Keys missing in the input are not added.
 * Callables in arrays (`[$object, 'method']`) would be taken for nested transformations, wrap them in a closure.
 *
 * @param array<mixed> $transformations
 * @param array<mixed> $array
 *
 * @return array<mixed>
 *
 * @category Object
 * @example  evolve(['count' => inc(...)], ['count' => 1, 'name' => 'a']); // ['count' => 2, 'name' => 'a']
 */
function evolve(array $transformations, array $array): array
{
    foreach ($transformations as $key => $transformation) {
        if (!array_key_exists($key, $array)) {
            continue;
        }

        if (is_array($transformation)) {
            if (is_array($array[$key])) {
                $array[$key] = evolve($transformation, $array[$key]);
            }
        } elseif (is_callable($transformation)) {
            $array[$key] = $transformation($array[$key]);
        }
    }

    return $array;
}

/**
 * Keeps the values matching the predicate. Keys are preserved; the key is passed as second argument if the predicate
 * accepts it.
 *
 * @param (callable(V): mixed)|(callable(V, K): mixed) $predicate
 * @param array<K, V>                                  $list
 *
 * @return array<K, V>
 *
 * @template K of array-key
 * @template V
 * @category List
 */
function filter(callable $predicate, array $list): array
{
    if (_acceptsArgs($predicate, 2)) {
        return array_filter($list, static fn ($value, $key) => (bool) $predicate($value, $key), ARRAY_FILTER_USE_BOTH);
    }

    return array_filter($list, static fn ($value) => (bool) $predicate($value));
}

/**
 * Returns the first element matching the predicate, `null` if there is none.
 *
 * @param callable(V): mixed $predicate
 * @param array<V>           $list
 *
 * @return V|null
 *
 * @template V
 * @category List
 */
function find(callable $predicate, array $list): mixed
{
    foreach ($list as $value) {
        if ($predicate($value)) {
            return $value;
        }
    }

    return null;
}

/**
 * Flattens nested arrays into one list, recursively.
 *
 * @param array<mixed> $list
 *
 * @return list<mixed>
 *
 * @category List
 * @example  flatten([1, [2, [3, 4]]]); // [1, 2, 3, 4]
 */
function flatten(array $list): array
{
    $result = [];
    foreach ($list as $value) {
        if (is_array($value)) {
            foreach (flatten($value) as $item) {
                $result[] = $item;
            }
        } else {
            $result[] = $value;
        }
    }

    return $result;
}

/**
 * Returns a function like the given one, but with the first two arguments swapped. It requires as many arguments as
 * the function, but at least two.
 *
 * @category Function
 * @example  flip(subtract(...))(10, 3); // -7
 */
function flip(callable $fn): Closure
{
    return _arity(
        phpMax(2, _getArity($fn)),
        static function (mixed ...$args) use ($fn) {
            [$args[0], $args[1]] = [$args[1], $args[0]];

            return $fn(...$args);
        },
    );
}

/**
 * Groups the elements by the key the function returns for them. Each group is a list.
 *
 * @param callable(V): array-key $fn
 * @param array<V>               $list
 *
 * @return array<array-key, list<V>>
 *
 * @template V
 * @category List
 * @example  groupBy(fn ($n) => $n % 2 ? 'odd' : 'even', [1, 2, 3]); // ['odd' => [1, 3], 'even' => [2]]
 */
function groupBy(callable $fn, array $list): array
{
    $groups = [];
    foreach ($list as $value) {
        $groups[$fn($value)][] = $value;
    }

    return $groups;
}

/**
 * `$a > $b`. With a placeholder, `gt(?, 5)` is "… is greater than 5".
 *
 * @category Relation
 * @example  filter(gt(?, 5), [3, 7]); // [1 => 7]
 */
function gt(mixed $a, mixed $b): bool
{
    return $a > $b;
}

/**
 * `$a >= $b`.
 *
 * @category Relation
 */
function gte(mixed $a, mixed $b): bool
{
    return $a >= $b;
}

/**
 * Whether the array has the key or the object has the public property, even if its value is `null`.
 *
 * @category Object
 */
function has(string|int $key, mixed $item): bool
{
    if (is_array($item)) {
        return array_key_exists($key, $item);
    }

    if (is_object($item)) {
        return array_key_exists($key, get_object_vars($item));
    }

    return false;
}

/**
 * The first element of a list (`null` if empty, keys are ignored) or the first character of a string.
 *
 * @param array<mixed>|string $list
 *
 * @category List
 */
function head(array|string $list): mixed
{
    if (is_string($list)) {
        return substr($list, 0, 1);
    }

    if ($list === []) {
        return null;
    }

    return $list[array_key_first($list)];
}

/**
 * Returns its argument unchanged.
 *
 * @param T $value
 *
 * @return T
 *
 * @template T
 * @category Function
 */
function identity(mixed $value): mixed
{
    return $value;
}

/**
 * Creates a function that calls $onTrue if $condition is satisfied, $onFalse otherwise. A null branch returns the
 * first argument unchanged. The function requires as many arguments as the callable with the most required
 * parameters.
 *
 * @category Logic
 * @example  ifElse(is_string(...), trim(...), null);
 */
function ifElse(callable $condition, callable|null $onTrue, callable|null $onFalse): Closure
{
    $arity = phpMax(
        _getArity($condition),
        $onTrue !== null ? _getArity($onTrue) : 0,
        $onFalse !== null ? _getArity($onFalse) : 0,
    );

    $identity = static fn (mixed ...$args) => $args[0] ?? null;
    $onTrue ??= $identity;
    $onFalse ??= $identity;

    return _arity(
        $arity,
        static fn (mixed ...$args) => $condition(...$args) ? $onTrue(...$args) : $onFalse(...$args),
    );
}

/**
 * Increments a number by 1.
 *
 * @category Math
 */
function inc(mixed $value): int|float
{
    return $value + 1;
}

/**
 * Whether the list contains an element equal to the needle (in `equals()` terms), or the string contains the
 * needle as substring.
 *
 * @param array<mixed>|string $haystack
 *
 * @category List
 * @example  includes(['a' => 1], [['a' => 1], ['b' => 2]]); // true
 * @example  includes('ell', 'hello'); // true
 */
function includes(mixed $needle, array|string $haystack): bool
{
    if (is_string($haystack)) {
        return is_string($needle) && str_contains($haystack, $needle);
    }

    foreach ($haystack as $value) {
        if (equals($needle, $value)) {
            return true;
        }
    }

    return false;
}

/**
 * Whether the value equals the empty value of its type (see `emptyVal()`), e.g. `''`, `[]`, `0`, `null` or an object
 * without properties. Unlike Ramda, `0` and `null` count as empty.
 *
 * @category Logic
 */
function isEmpty(mixed $value): bool
{
    return equals($value, emptyVal($value));
}

/**
 * Whether the value is `null`.
 *
 * @category Type
 */
function isNil(mixed $value): bool
{
    return $value === null;
}

/**
 * Returns a string made by inserting the separator between each element and concatenating all the elements into a
 * single string.
 *
 * @param array<mixed> $list
 *
 * @category List
 */
function join(string $separator, array $list): string
{
    return implode($separator, $list);
}

/**
 * The keys of an array, or the names of an object's public properties.
 *
 * @param array<mixed>|object $item
 *
 * @return list<array-key>
 *
 * @category Object
 */
function keys(array|object $item): array
{
    return array_keys(is_object($item) ? get_object_vars($item) : $item);
}

/**
 * The last element of a list (`null` if empty) or the last character of a string.
 *
 * @param array<mixed>|string $list
 *
 * @category List
 */
function last(array|string $list): mixed
{
    if (is_string($list)) {
        return substr($list, -1);
    }

    if ($list === []) {
        return null;
    }

    return $list[array_key_last($list)];
}

/**
 * `$a && $b`.
 *
 * @category Logic
 */
function logicAnd(mixed $a, mixed $b): bool
{
    return $a && $b;
}

/**
 * `$a || $b`.
 *
 * @category Logic
 */
function logicOr(mixed $a, mixed $b): bool
{
    return $a || $b;
}

/**
 * `$a xor $b`.
 *
 * @category Logic
 */
function logicXor(mixed $a, mixed $b): bool
{
    return $a xor $b;
}

/**
 * `$a < $b`. With a placeholder, `lt(?, 5)` is "… is less than 5".
 *
 * @category Relation
 */
function lt(mixed $a, mixed $b): bool
{
    return $a < $b;
}

/**
 * `$a <= $b`.
 *
 * @category Relation
 */
function lte(mixed $a, mixed $b): bool
{
    return $a <= $b;
}

/**
 * Applies the function to every value of the list. Keys are preserved, `null` returns `[]`. The function only
 * receives the value.
 *
 * @param callable(V): R   $fn
 * @param array<K, V>|null $list
 *
 * @return array<K, R>
 *
 * @template K of array-key = never
 * @template V = never
 * @template R = mixed
 * @category List
 */
function map(callable $fn, array|null $list): array
{
    if ($list === null) {
        return [];
    }

    return array_map($fn, $list);
}

/**
 * The larger of two values.
 *
 * @param T $a
 * @param T $b
 *
 * @return T
 *
 * @template T
 * @category Relation
 */
function max(mixed $a, mixed $b): mixed
{
    return $b > $a ? $b : $a;
}

/**
 * The arithmetic mean of the list, `NAN` for an empty list.
 *
 * @param array<int|float> $list
 *
 * @category Math
 */
function mean(array $list): float
{
    return fdiv(array_sum($list), count($list));
}

/**
 * `array_merge()` of two arrays: for string keys the value of $b wins, numeric keys are appended. Use `concat()` to
 * join lists.
 *
 * @param array<mixed> $a
 * @param array<mixed> $b
 *
 * @return array<mixed>
 *
 * @category List
 */
function merge(array $a, array $b): array
{
    return array_merge($a, $b);
}

/**
 * The smaller of two values.
 *
 * @param T $a
 * @param T $b
 *
 * @return T
 *
 * @template T
 * @category Relation
 */
function min(mixed $a, mixed $b): mixed
{
    return $b < $a ? $b : $a;
}

/**
 * Multiplies two numbers. `null`, `false` and `''` count as 0; numbers keep their type, so `multiply(0.0, 1.5)` is
 * `0.0`.
 *
 * @category Math
 */
function multiply(mixed $a, mixed $b): int|float
{
    $orZero = static fn ($value) => $value === null || $value === false || $value === '' ? 0 : $value;

    return $orZero($a) * $orZero($b);
}

/**
 * Returns `true` if no elements of the list match the predicate, `false` otherwise. Stops at the first match.
 *
 * @param callable(mixed): mixed $predicate
 * @param array<mixed>           $list
 *
 * @category List
 */
function none(callable $predicate, array $list): bool
{
    return !any($predicate, $list);
}

/**
 * `!$value`: `true` for falsy values, `false` for truthy ones.
 *
 * @category Logic
 */
function not(mixed $value): bool
{
    return !$value;
}

/**
 * The element at the position (ignoring keys), counted from the end if negative. `null` if out of range.
 * For strings the character at the position, `''` if out of range.
 *
 * @param array<mixed>|string $list
 *
 * @category List
 */
function nth(int $offset, array|string $list): mixed
{
    if (is_string($list)) {
        return substr($list, $offset, 1);
    }

    $values = array_values($list);
    $index = $offset < 0 ? count($values) + $offset : $offset;

    return $values[$index] ?? null;
}

/**
 * A copy of the array without the given keys.
 *
 * @param array<array-key> $keys
 * @param array<K, V>      $array
 *
 * @return array<K, V>
 *
 * @template K of array-key
 * @template V
 * @category Object
 */
function omit(array $keys, array $array): array
{
    foreach ($keys as $key) {
        unset($array[$key]);
    }

    return $array;
}

/**
 * Splits the list into the elements matching the predicate and the others: `[$matching, $others]`.
 * Keys are preserved; the key is passed as second argument if the predicate accepts it.
 *
 * @param (callable(V): mixed)|(callable(V, K): mixed) $predicate
 * @param array<K, V>                                  $list
 *
 * @return array{array<K, V>, array<K, V>}
 *
 * @template K of array-key
 * @template V
 * @category List
 */
function partition(callable $predicate, array $list): array
{
    $withKey = _acceptsArgs($predicate, 2);
    $matching = [];
    $others = [];
    foreach ($list as $key => $value) {
        if ($withKey ? $predicate($value, $key) : $predicate($value)) {
            $matching[$key] = $value;
        } else {
            $others[$key] = $value;
        }
    }

    return [$matching, $others];
}

/**
 * The nested value at the path, `null` if any key on the way is missing.
 *
 * @param array<array-key> $path
 *
 * @category Object
 * @example  path(['address', 'city'], $user);
 */
function path(array $path, mixed $item): mixed
{
    return paths([$path], $item)[0];
}

/**
 * Whether the nested value equals the given value (in `equals()` terms). Value first, like Ramda >= 0.29.
 *
 * @param array<array-key> $path
 *
 * @category Relation
 * @example  pathEq('Berlin', ['address', 'city'], $user);
 */
function pathEq(mixed $value, array $path, mixed $item): bool
{
    return equals(path($path, $item), $value);
}

/**
 * The nested value, or the default if it is missing or `null`.
 *
 * @param array<array-key> $path
 *
 * @category Object
 */
function pathOr(mixed $default, array $path, mixed $item): mixed
{
    return path($path, $item) ?? $default;
}

/**
 * Several `path()` lookups at once, returns their values as a list.
 *
 * @param array<array<array-key>> $paths
 *
 * @return array<mixed>
 *
 * @category Object
 */
function paths(array $paths, mixed $item): array
{
    return array_map(
        static function (array $path) use ($item) {
            $value = $item;
            foreach ($path as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) {
                    return null;
                }

                $value = $value[$key];
            }

            return $value;
        },
        $paths,
    );
}

/**
 * Whether the nested value satisfies the predicate.
 *
 * @param array<array-key> $path
 *
 * @category Logic
 * @example  pathSatisfies(isEmpty(...), ['address', 'zip'], $user);
 */
function pathSatisfies(callable $predicate, array $path, mixed $item): bool
{
    return (bool) $predicate(path($path, $item));
}

/**
 * A copy of the array with the given keys only, in the order of the keys. Missing keys are ignored.
 *
 * @param array<array-key> $keys
 * @param array<K, V>      $array
 *
 * @return array<K, V>
 *
 * @template K of array-key
 * @template V
 * @category Object
 */
function pick(array $keys, array $array): array
{
    $result = [];
    foreach ($keys as $key) {
        if (!array_key_exists($key, $array)) {
            continue;
        }

        $result[$key] = $array[$key];
    }

    return $result;
}

/**
 * Left-to-right function composition. The first function may have any arity, the others must be unary. The result
 * requires as many arguments as the first function. Throws InvalidArgumentException without functions. To run a
 * value through functions right away, use the pipe operator: `$value |> trim(...) |> strtoupper(...)`.
 *
 * @category Function
 * @example  pipe(trim(...), strtoupper(...))(' a '); // 'A'
 */
function pipe(callable ...$fns): Closure
{
    if ($fns === []) {
        throw new InvalidArgumentException('pipe requires at least one argument');
    }

    $first = array_shift($fns);

    return _arity(
        _getArity($first),
        static function (mixed ...$args) use ($first, $fns) {
            $result = $first(...$args);
            foreach ($fns as $fn) {
                $result = $fn($result);
            }

            return $result;
        },
    );
}

/**
 * The given key (or public property) of each element, see `prop()`. Keys are preserved.
 *
 * @param array<K, mixed> $list
 *
 * @return array<K, mixed>
 *
 * @template K of array-key
 * @category List
 * @example  pluck('name', [['name' => 'Ada'], ['name' => 'Linus']]); // ['Ada', 'Linus']
 */
function pluck(string|int $key, array $list): array
{
    return array_map(static fn ($item) => prop($key, $item), $list);
}

/**
 * The value of an array key or of a public object property, `null` if it doesn't exist. Works on lists as well.
 *
 * @category Object
 * @example  prop('x', ['x' => 100]); // 100
 * @example  map(prop('name', ?), $users);
 */
function prop(string|int $key, mixed $item): mixed
{
    if (is_array($item) && array_key_exists($key, $item)) {
        return $item[$key];
    }

    if (is_object($item)) {
        $vars = get_object_vars($item);
        if (array_key_exists($key, $vars)) {
            return $vars[$key];
        }
    }

    return null;
}

/**
 * Whether the property equals the given value (in `equals()` terms). Value first, like Ramda >= 0.29.
 *
 * @category Relation
 * @example  filter(propEq('active', 'status', ?), $users);
 */
function propEq(mixed $value, string|int $key, mixed $item): bool
{
    return equals(prop($key, $item), $value);
}

/**
 * The property, or the default if it is missing or `null`.
 *
 * @category Object
 */
function propOr(mixed $default, string|int $key, mixed $item): mixed
{
    return prop($key, $item) ?? $default;
}

/**
 * Whether the property satisfies the predicate.
 *
 * @category Logic
 * @example  ifElse(propSatisfies(isEmpty(...), 'rim_brand_id', ?), always(null), applySpec([...]));
 */
function propSatisfies(callable $predicate, string|int $key, mixed $item): bool
{
    return (bool) $predicate(prop($key, $item));
}

/**
 * Reduces the list to a single value: calls the function with the accumulator and each value (and the key, if the
 * function accepts a third argument) and passes the result on as the next accumulator.
 *
 * @param (callable(A, V): A)|(callable(A, V, K): A) $fn
 * @param A                                          $acc
 * @param array<K, V>                                $list
 *
 * @return A
 *
 * @template A
 * @template K of array-key
 * @template V
 * @category List
 */
function reduce(callable $fn, mixed $acc, array $list): mixed
{
    $withKey = _acceptsArgs($fn, 3);
    foreach ($list as $key => $value) {
        $acc = $withKey ? $fn($acc, $value, $key) : $fn($acc, $value);
    }

    return $acc;
}

/**
 * The complement of `filter()`: keeps the elements not matching the predicate. Keys are preserved; the key is
 * passed as second argument if the predicate accepts it.
 *
 * @param (callable(V): mixed)|(callable(V, K): mixed) $predicate
 * @param array<K, V>                                  $list
 *
 * @return array<K, V>
 *
 * @template K of array-key
 * @template V
 * @category List
 */
function reject(callable $predicate, array $list): array
{
    return partition($predicate, $list)[1];
}

/**
 * Not part of Ramda, specific to this library.
 *
 * Restructures a flat record, e.g. a database row, with a spec like `applySpec()`, but:
 *
 * - a string in the spec is a key of the record: its value is moved there, and the key is removed from the
 *   record (missing keys give `null`),
 * - arrays are nested specs,
 * - callables are called with the whole record and don't remove anything. Strings are always keys, so pass named
 *   functions as closures (`trim(...)`), and wrap array callables (`[$object, 'method']`) in a closure.
 *
 * All other keys of the record are kept. Keys of the spec win over remaining record keys; with the former
 * `converge(merge(...), [applySpec(...), omit(...)])` pattern a remaining record key won instead. An `omit()` after
 * `remap()` (e.g. in `pipe()`) runs on the result, so it must not list keys the spec produces.
 *
 * @param array<mixed> $spec
 * @param array<mixed> $record
 *
 * @return array<mixed>
 *
 * @category Object
 * @example  remap(['wheelhotel' => ['id' => 'wheelhotel_id', 'name' => 'wheelhotel_name']], $record);
 */
function remap(array $spec, array $record): array
{
    $moved = [];
    $build = static function (array $spec) use (&$build, &$moved, $record): array {
        $result = [];
        foreach ($spec as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $record[$value] ?? null;
                $moved[$value] = true;
            } elseif (is_array($value)) {
                $result[$key] = $build($value);
            } elseif (is_callable($value)) {
                $result[$key] = $value($record);
            } else {
                throw new InvalidArgumentException('remap spec values must be strings, arrays or callables');
            }
        }

        return $result;
    };

    $result = $build($spec);

    return $result + array_diff_key($record, $moved);
}

/**
 * Reverses the list. String keys are kept, numeric keys renumbered, like `array_reverse()`.
 *
 * @param array<mixed> $list
 *
 * @return array<mixed>
 *
 * @category List
 */
function reverse(array $list): array
{
    return array_reverse($list);
}

/**
 * The elements or characters from `$from` (inclusive) to `$to` (exclusive), like Ramda's slice. Negative
 * indices count from the end, `null` as `$to` means "until the end". Lists keep string keys like `array_slice()`.
 *
 * @param array<mixed>|string $list
 *
 * @return array<mixed>|string
 *
 * @category List
 * @example  slice(11, 19, '1753-01-01 08:15:00.000'); // '08:15:00'
 */
function slice(int $from, int|null $to, array|string $list): array|string
{
    $length = is_string($list) ? strlen($list) : count($list);
    $start = $from < 0 ? phpMax($length + $from, 0) : phpMin($from, $length);
    $end = $to === null ? $length : ($to < 0 ? phpMax($length + $to, 0) : phpMin($to, $length));
    $count = phpMax($end - $start, 0);

    return is_string($list) ? substr($list, $start, $count) : array_slice($list, $start, $count);
}

/**
 * Sorts the list by the values the function returns, ascending (stable, compared with `<=>`). Returns a list.
 *
 * @param callable(V): mixed $fn
 * @param array<V>           $list
 *
 * @return list<V>
 *
 * @template V
 * @category List
 * @example  sortBy(prop('age', ?), $users);
 */
function sortBy(callable $fn, array $list): array
{
    $decorated = array_map(static fn ($value) => [$fn($value), $value], array_values($list));
    usort($decorated, static fn (array $a, array $b) => $a[0] <=> $b[0]);

    return array_column($decorated, 1);
}

/**
 * Sorts the list by the comparators: the first one decides, the next ones break ties (stable). Use with
 * `ascend()` and `descend()`. Returns a list.
 *
 * @param array<callable(V, V): int> $comparators
 * @param array<V>                   $list
 *
 * @return list<V>
 *
 * @template V
 * @category List
 * @example  sortWith([ascend(prop('carhouse_id', ?)), ascend(prop('id', ?))], $records);
 */
function sortWith(array $comparators, array $list): array
{
    $list = array_values($list);
    usort($list, static function ($a, $b) use ($comparators): int {
        foreach ($comparators as $comparator) {
            $result = $comparator($a, $b);
            if ($result !== 0) {
                return $result;
            }
        }

        return 0;
    });

    return $list;
}

/**
 * Splits a string into a list of strings at the separator.
 *
 * @return list<string>
 *
 * @category List
 */
function split(string $separator, string $string): array
{
    if ($separator === '') {
        throw new InvalidArgumentException('split requires a non-empty separator');
    }

    return explode($separator, $string);
}

/**
 * `$a - $b`. `null` counts as 0.
 *
 * @category Math
 */
function subtract(mixed $a, mixed $b): int|float
{
    return ($a ?? 0) - ($b ?? 0);
}

/**
 * Adds together all the elements of a list. Returns 0 for an empty list.
 *
 * @param array<mixed> $list
 *
 * @category Math
 */
function sum(array $list): int|float
{
    return array_sum($list);
}

/**
 * All but the first element of a list (string keys are preserved, numeric keys renumbered like
 * `array_shift()`) or all but the first character of a string.
 *
 * @param array<mixed>|string $list
 *
 * @return array<mixed>|string
 *
 * @category List
 */
function tail(array|string $list): array|string
{
    if (is_string($list)) {
        return substr($list, 1);
    }

    array_shift($list);

    return $list;
}

/**
 * The first `$count` elements of a list (like `array_slice()`: string keys are kept, numeric keys renumbered) or
 * characters of a string.
 *
 * @param array<mixed>|string $list
 *
 * @return array<mixed>|string
 *
 * @category List
 */
function take(int $count, array|string $list): array|string
{
    if (is_array($list)) {
        return array_slice($list, 0, $count);
    }

    return substr($list, 0, $count);
}

/**
 * Calls the function with the value and returns the value. Useful for debugging inside `pipe()` or `|>`.
 *
 * @param callable(T): mixed $fn
 * @param T                  $value
 *
 * @return T
 *
 * @template T
 * @category Function
 * @example  $list |> map($fn, ?) |> tap(var_dump(...), ?) |> filter($predicate, ?);
 */
function tap(callable $fn, mixed $value): mixed
{
    $fn($value);

    return $value;
}

/**
 * Returns a function that takes any number of arguments and passes them to the given function as one array
 * (named arguments keep their names as keys).
 *
 * @param callable(array<mixed>): mixed $fn
 *
 * @return Closure(mixed...): mixed
 *
 * @category Function
 */
function unapply(callable $fn): Closure
{
    return static fn (mixed ...$args) => $fn($args);
}

/**
 * The list without duplicates (in `equals()` terms), keeping the first occurrence. Returns a list.
 * Compares each element with all kept ones (quadratic), so use array_unique() for large lists of scalars.
 *
 * @param array<V> $list
 *
 * @return list<V>
 *
 * @template V
 * @category List
 */
function uniq(array $list): array
{
    $result = [];
    foreach ($list as $value) {
        foreach ($result as $existing) {
            if (equals($existing, $value)) {
                continue 2;
            }
        }

        $result[] = $value;
    }

    return $result;
}

/**
 * Returns the value unchanged if the predicate is satisfied, otherwise the result of the function.
 *
 * @category Logic
 * @example  unless(isNil(...), trim(...), $value); // trims strings, keeps null
 */
function unless(callable $predicate, callable $fn, mixed $value): mixed
{
    return $predicate($value) ? $value : $fn($value);
}

/**
 * The values of an array as list, or the values of an object's public properties.
 *
 * @param array<mixed>|object $item
 *
 * @return list<mixed>
 *
 * @category Object
 */
function values(array|object $item): array
{
    return array_values(is_object($item) ? get_object_vars($item) : $item);
}

/**
 * Returns the result of the function if the predicate is satisfied, otherwise the value unchanged.
 *
 * @category Logic
 * @example  when(is_string(...), trim(...), $value);
 */
function when(callable $predicate, callable $fn, mixed $value): mixed
{
    return $predicate($value) ? $fn($value) : $value;
}

/**
 * How many arguments the callable expects (arity): the number of its required parameters, like Function.length in
 * JavaScript. Optional and variadic parameters don't count. For a partial application like `foo(1, ?)` these are
 * the placeholders.
 */
function _getArity(callable $callable): int
{
    return _reflectCallable($callable)->getNumberOfRequiredParameters();
}

/**
 * Whether the callable can safely be called with $n arguments without dropping or rejecting them.
 * Internal functions and partial applications (`trim(?, ...)`) only count their required parameters, so optional
 * parameters (like trim()'s $characters) don't receive unrelated values.
 */
function _acceptsArgs(callable $callable, int $n): bool
{
    $r = _reflectCallable($callable);

    if ($r->isVariadic()) {
        return true;
    }

    // partial applications keep the optional parameters of the function they wrap, which may well be internal
    if ($r->isInternal() || str_starts_with($r->getName(), '{closure:pfa:')) {
        return $r->getNumberOfRequiredParameters() >= $n;
    }

    return $r->getNumberOfParameters() >= $n;
}

function _reflectCallable(callable $callable): ReflectionFunction
{
    return new ReflectionFunction(Closure::fromCallable($callable));
}

/**
 * Wraps the function in a closure with $n required parameters, so that `_getArity()` sees them. All arguments are
 * passed on.
 *
 * @throws RuntimeException
 */
function _arity(int $n, callable $fn): Closure
{
    // phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter

    return match ($n) {
        0 => static fn () => $fn(...func_get_args()),
        1 => static fn ($a0) => $fn(...func_get_args()),
        2 => static fn ($a0, $a1) => $fn(...func_get_args()),
        3 => static fn ($a0, $a1, $a2) => $fn(...func_get_args()),
        4 => static fn ($a0, $a1, $a2, $a3) => $fn(...func_get_args()),
        5 => static fn ($a0, $a1, $a2, $a3, $a4) => $fn(...func_get_args()),
        6 => static fn ($a0, $a1, $a2, $a3, $a4, $a5) => $fn(...func_get_args()),
        7 => static fn ($a0, $a1, $a2, $a3, $a4, $a5, $a6) => $fn(...func_get_args()),
        8 => static fn ($a0, $a1, $a2, $a3, $a4, $a5, $a6, $a7) => $fn(...func_get_args()),
        9 => static fn ($a0, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8) => $fn(...func_get_args()),
        10 => static fn ($a0, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8, $a9) => $fn(...func_get_args()),
        default => throw new RuntimeException(
            'First argument to _arity must be a non-negative integer no greater than ten',
        ),
    };

    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
}
