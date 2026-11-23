# CLAUDE.md

Ramda-inspired functional programming library for PHP: package `temp/fnc` (until 1.0 `temp/fn`), namespace `Fnc`. See README.md for the public API.

## Layout

- `src/functions.php` – the whole library: plain namespaced functions, loaded via Composer `files` autoloading.
  The PSR-4 entry for `Fnc\` exists but there are no classes.
- `tests/Unit/<Function>Test.php` – one test class per public function, `#[CoversFunction('Fnc\…')]`.
  Tests for internal `_foo` helpers are named `Internal<Foo>Test` (phpcs forbids leading underscores in class names).
- `tests/Integration/` – combinations of several functions.
- `examples/` – runnable scripts (`php examples/01-remap.php`). Not part of the test suite, but CI runs all of them
  and fails on any error, warning, notice or deprecation.

## Commands

```bash
vendor/bin/phpunit                     # tests
vendor/bin/phpunit --filter EqualsTest # single test class
vendor/bin/phpstan analyze             # static analysis (level 8)
vendor/bin/phpcs                       # coding standard (brainbits/phpcs-standard), phpcbf to fix
```

CI (`.github/workflows/build.yml`) runs composer validate/audit, phpcs and phpstan on PHP 8.6 and the tests on
PHP 8.6 with lowest and highest dependencies. phpcs only runs with the highest dependencies; the lowest
coding standard version reports different violations. `.github/workflows/code_coverage.yaml` uploads coverage (pcov,
PHP 8.6) to Codecov. Line coverage is 100 %; `#[CoversFunction]` only counts lines of the named function, so internal
helpers need their own `Internal*Test`.

## How functions are built

Since 2.0 functions are plain typed functions without auto-currying; partial application is left to PHP 8.6
(`foo(1, ?)`, `foo(...)`) and the pipe operator `|>`. Keep this pattern for new functions:

```php
/**
 * What the function does.
 *
 * @param callable(V): mixed $fn
 * @param array<K, V>        $list
 *
 * @return array<K, V>
 *
 * @template K of array-key
 * @template V
 * @category List
 * @example  foo($fn, [1, 2]);
 * @example  map(foo($fn, ?), $lists);
 */
function foo(callable $fn, array $list): array
{
    // implementation
}
```

- Argument order is "data last" like Ramda, so `foo($config, ?)` is the natural partial application.
- Functions whose job is to build a function (`applySpec`, `ifElse`, `allPass`, `ascend`, `converge`, …) take only
  the configuration and return a `Closure`. All others take the data as last argument.
- Use `@template` generics where they help callers (list functions). Keep `mixed` where a narrower type would change
  behaviour: the math functions accept numeric strings and `null` from database rows.
- `_getArity()` counts **required** parameters only (like JS `Function.length`); for a partial application these
  are the `?` placeholders. It drives the arity of the closures returned by `applySpec`, `ifElse`, `flip` and `pipe`
  (via `_arity()`).
- `_acceptsArgs()` answers a different question: may a callback receive an extra argument (the key in `filter`,
  `partition` and `reduce`)? Internal PHP functions throw `ArgumentCountError` on surplus arguments, and their
  optional parameters must not receive keys, so only required parameters count for them and for partial
  applications (closure name `{closure:pfa:…}`), which keep the optional parameters of the wrapped function.
- Functions prefixed with `_` are internal.
- The library defines `Fnc\max`, `Fnc\min` and `Fnc\clamp`, so PHP's functions are imported as `phpMax`/`phpMin`/
  `phpClamp` in `src/functions.php`. A plain `max(...)` call inside the namespace would hit `Fnc\max`. The same
  applies to any new function named like a PHP builtin.
- PHP_CodeSniffer 4.0 doesn't know partial application yet and takes `?` for the ternary operator, so
  `phpcs.xml.dist` excludes `PSR12.Operators.OperatorSpacing.NoSpaceBefore/NoSpaceAfter`. Remove that once it does.

## Semantics decisions (don't silently change)

- `equals`: strict like Ramda – list order and length matter, associative key order doesn't, objects need the same
  class, closures compare by identity, `NAN` equals `NAN`, cycles are handled. Unlike Ramda, `1` and `1.0` differ.
- `divide`: like Ramda – `null`, `false` and `''` count as 0, division by zero (also `'0'`) returns `INF`/`-INF`/`NAN`
  via `fdiv()`.
- `isEmpty(0)` and `isEmpty(null)` are `true` (differs from Ramda).
- `filter` and `map` preserve keys.
- `ifElse` treats a `null` branch as identity of the first argument.
- `propEq` / `pathEq` take the value first (Ramda >= 0.29 order).
- Comparisons take the left operand first: `gt($a, $b)` is `$a > $b`, `gt(?, 5)` is "… > 5". `ascend`/`descend`
  take the function and return a comparator using `<=>` (numeric strings compare numerically);
  `ascendWith`/`descendWith` take a comparison function such as `strcmp(...)` first.
- `clamp` takes `($min, $max, $value)` like Ramda (data last) and calls PHP's `clamp($value, $min, $max)`.
- `sortBy`, `sortWith`, `uniq`, `chain`, `flatten` return lists; `filter`, `reject`, `partition`, `pluck` preserve
  keys.
- `remap` is our own addition, Ramda has no equivalent. Strings in the spec are always record keys (moved and removed), never callables; arrays are nested specs;
  callables get the whole record and remove nothing. Spec keys win over remaining record keys (the old
  merge/applySpec/omit pattern let record keys win); an `omit()` after `remap()` runs on the result. Built for the
  `converge(merge(...), [applySpec(...), omit(..., ?)])` pattern in the consuming projects' SQL read models.

## Conventions

- PHP `^8.6`, `declare(strict_types=1)`, global functions imported with `use function`.
- Every bug fix gets a regression test that fails without the fix.
- Conventional Commits are required: semantic-release (`.releaserc`, `.github/workflows/release.yaml`) creates tags
  without `v` prefix (`1.0.0`) and GitHub releases from commits on `main`. `fix:` → patch, `feat:` → minor,
  `BREAKING CHANGE:` footer → major. The default (angular) preset ignores a bare `!` in the header, so always add
  the footer. Only mark real breaking changes.
- Releases run after a green "Build" on main (`workflow_run`). semantic-release is pinned in `release.yaml`; bump it
  deliberately. Merge PRs with a merge commit, a squash keeps only the PR title.
- `main` is protected by a ruleset: changes go through pull requests with green CI checks.

## Versions

1.x is the auto-curried API for PHP 8.4 and 8.5 (it runs on 8.6 as well).
2.x requires PHP 8.6 and replaces auto-currying with typed signatures plus PHP's partial function application and
pipe operator.
