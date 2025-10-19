# Contributing Guide

Thank you for taking the time to contribute! 🎉

This project welcomes pull requests, bug reports, and feature ideas to help improve the package for the PHP community.

## Code Style

This project adheres to PSR-12 as the base coding standard. However, code style is fine-tuned and enforced
by [StyleCI](https://styleci.io/) to reflect additional project preferences, for example:

* Always using curly braces in string interpolation
* Consistent string literal style (single or double quotes where preferred)
* Minor whitespace, alignment, or readability adjustments

Please don’t worry about matching every rule manually; StyleCI will automatically reformat your pull request once it’s
submitted.

### PHPDoc

PHPDoc blocks should be used to provide type information where it cannot be inferred from type declarations alone, for
example:

```php
/**
 * @return array<string, mixed>
 */
public function getConfig(): array
{
    // ...
}
```

For more complex types, such as nested arrays or generics, please refer to
the [static analysis section](#static-analysis) below for guidance on using custom types to improve readability.

### Local Formatting

If you'd prefer to format your code locally, before submitting a pull request, you can
use [PHP CS Fixer](https://cs.symfony.com/) with the following rules:

```php
[
    '@PSR12' => true,
    '@PhpCsFixer' => true,
    'assign_null_coalescing_to_coalesce_equal' => true,
    'concat_space' => ['spacing' => 'one'],
    'group_import' => true,
    'heredoc_indentation' => true,
    'increment_style' => ['style' => 'post'],
    'multiline_promoted_properties' => true,
    'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    'new_expression_parentheses' => true,
    'not_operator_with_successor_space' => true,
    'octal_notation' => true,
    'ordered_attributes' => true,
    'ordered_interfaces' => true,
    'phpdoc_align' => ['align' => 'left'],
    'phpdoc_to_comment' => ['allow_before_return_statement' => true, 'ignored_tags' => ['todo']],
    'simplified_if_return' => true,
    'simplified_null_return' => true,
    'single_import_per_statement' => false,
    'ternary_to_null_coalescing' => true,
    'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
]
```

---

## Testing

This project uses [Pest](https://pestphp.com), for testing, chosen for several reasons:

1. **Expressive syntax:** Pest’s syntax is clean and readable, making tests easy to write and understand.
2. **Great console output:** Pest’s test runner provides clear and visually appealing feedback.
3. **Ecosystem of plugins:** Pest plugins (such as the profanity plugin) help in maintaining a clean and professional
   codebase.
4. **Mutation testing:** Pest can introduce small changes (mutations) to the code to see if tests catch them, ensuring
   the code is thoroughly tested.

All tests are located in the `tests/` directory. Tests can be run a variety of ways using Pest's CLI tool or, for
convenience, most commonly used commands (and their args) have been added to the project's `composer.json`:

```bash
composer test               # Run the test suite
composer test:dirty         # Run only tests affected by recent changes
composer test:coverage      # Run tests with code coverage report
composer test:mutate        # Run mutation testing
composer test:type-coverage # Run type coverage analysis
```

---

## Static Analysis

In addition to unit, feature, or integration testing, this project uses [PHPStan](https://phpstan.org/) for static
analysis; typically configured at level 5 for a balanced level of strictness.

While Pest includes support for type coverage testing, static analysis serves a different and complementary purpose:

* Type coverage ensures that code uses and enforces type declarations consistently.
* Static analysis inspects code paths, generics, and edge cases that tests may not cover, catching potential issues
  before they manifest at runtime.
* Running PHPStan helps identify dead code, invalid assumptions, and incorrect type hints early in the development
  process.

Some code may have repeated generic types, where more verbosity might be desired. This can affect the readability of a
PHPDoc block or, depending on your code editor or IDE, might become a pain to copy-paste. Instead, defining a custom
type, similar to what can be done in languages like TypeScript, might be a better solution.

### Custom Type Example

> [!NOTE] Be sure to read
> the [PHPStan documentation on custom types](https://phpstan.org/developing-extensions/custom-phpdoc-types) before going
> down this route

Without a custom type, we'd be copy-pasting this block everywhere it's needed:

```php
/**
 * @return array<string, {
 *      src?: string
 *      file: string
 *      css?: string[]
 *      assets?: string[]
 *      isEntry?: bool
 *      name?: string
 *      names?: string[]
 *      isDynamicEntry?: bool
 *      imports?: string[]
 *      dynamicImports?: string[]
 * }>
 */
```

With a custom type defined at the start of the class that needs it, we can simplify the above to:

```php
/**
 * @phpstan-type ViteManifestChunk array{
 *      src?: string
 *      file: string
 *      css?: string[]
 *      assets?: string[]
 *      isEntry?: boolean
 *      name?: string
 *      names?: string[]
 *      isDynamicEntry?: boolean
 *      imports?: string[]
 *      dynamicImports?: string[]
 * }
 */
```

And then use it like so:

```php
/**
 * @return array<string, ViteManifestChunk>
 */
```

---

## Commit Conventions

This project follows the [Conventional Commits](https://www.conventionalcommits.org/) specification to keep commit
messages consistent and automation-friendly.

### Format

Each commit message should follow this general structure:

```
<type>[optional scope]: <description>
```

#### Examples

Without scope:

```text
feat: add support for multiple manifest files
```

With scope:

```text
fix(tests): correct missing CSS assertion in asset output
```

#### Common Types found in project

| Type       | Description                                    |
|------------|------------------------------------------------|
| `feat`     | A new feature                                  |
| `fix`      | A bug fix                                      |
| `docs`     | Documentation-only changes                     |
| `test`     | Adding or improving tests                      |
| `chore`    | Maintenance or tooling changes                 |
| `refactor` | Code refactors that don’t affect functionality |
| `style`    | Code style or formatting-only changes          |

Following this format helps with automated changelogs, semantic versioning, and clearer project history.

---

## Development Setup

1. Fork and clone the repository.
2. Run `composer install` to install dependencies.
3. Run `composer test` to ensure everything is set up correctly.
4. Create a new branch for your feature or bug fix.
   > [!NOTE] Please ensure your branch name is descriptive, for example: `feat/add-multiple-manifest-support` or
   `fix/asset-url-resolution-bug`.
5. Make your changes, ensuring to follow commit conventions outlined above.

---

## Submitting Changes

Before submitting a pull request, please ensure:

- Commits are focused and atomic.
- Update any relevant documentation or examples.
- All tests pass and new tests are added for any new features.
- Run static analysis to ensure no new issues are introduced.

When ready, open a pull request against the `develop` branch, using the appropriate pull request template.

---

## Feature Requests & Bug Reports

If you’ve found a bug or have an idea for improvement:

1. Check [existing issues](../../issues) to avoid duplicates.
2. Open a new issue using the appropriate template (bug report or feature request).

---

## Questions or Discussions

If you have general questions, ideas, or want to discuss implementation details, start a [discussion](../../discussions)
&mdash; contributions aren’t limited to code.

---

## License

By contributing, you agree that your submissions will be licensed under the same [MIT license](../LICENSE) as the
project.
