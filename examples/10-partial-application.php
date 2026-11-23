<?php

include __DIR__.'/../vendor/autoload.php';

use function Fnc\add;
use function Fnc\apply;
use function Fnc\clamp;
use function Fnc\dec;
use function Fnc\divide;
use function Fnc\filter;
use function Fnc\flip;
use function Fnc\identity;
use function Fnc\inc;
use function Fnc\map;
use function Fnc\max;
use function Fnc\min;
use function Fnc\multiply;
use function Fnc\pipe;
use function Fnc\subtract;

// Functions take all their arguments. PHP's partial function application fills some of them now and marks the
// others with ?, which returns a closure waiting for the rest.
$addTen = add(10, ?);
echo '$addTen(5) = ' . $addTen(5) . PHP_EOL;

// Partial applications plug directly into map(), pipe() and friends.
print_r(map(add(1, ?), [1, 2, 3]));

// The placeholder can stand for any argument, so the operand order is explicit.
$minusTen = subtract(?, 10);
echo '$minusTen(3) = ' . $minusTen(3) . PHP_EOL;
// flip() swaps the first two arguments
echo 'flip(subtract(...))(10, 3) = ' . flip(subtract(...))(10, 3) . PHP_EOL;

// Works with your own functions and closures as well.
$formatPrice = static fn (string $currency, int $decimals, float $amount) => number_format($amount, $decimals) . ' ' . $currency;

$euro = $formatPrice('EUR', 2, ?);
$wholeYen = $formatPrice('JPY', 0, ?);

echo $euro(1234.5) . PHP_EOL;
echo $wholeYen(1234.5) . PHP_EOL;

// Combine everything into reusable pipelines.
$netPriceLabels = pipe(
    map(static fn (float $gross) => $gross / 1.19, ?),
    map($euro, ?),
);
print_r($netPriceLabels([119.0, 59.5]));

// The pipe operator runs a value through functions right away.
print_r([119.0, 59.5] |> map(divide(?, 1.19), ?) |> map($euro, ?));

// Math helpers. max(0, ?) is "the larger of 0 and …", clamp() restricts to a range.
print_r(map(max(0, ?), [-5, 3, -1]));
print_r(map(min(10, ?), [5, 30]));
print_r(map(clamp(0, 100, ?), [-5, 50, 150]));
// Functions that need just the value are passed as first-class callables.
print_r(map(inc(...), [1, 2]));
print_r(map(dec(...), [1, 2]));
print_r(map(multiply(2, ?), [1.5, 4]));
print_r(map(divide(?, 100), [50, 125]));

// identity() returns its argument, e.g. to keep only truthy values
print_r(filter(identity(...), [0, 1, '', 'a', null]));

// apply() calls a function with an array of arguments
echo 'apply(max(...), [3, 7]) = ' . apply(max(...), [3, 7]) . PHP_EOL;
