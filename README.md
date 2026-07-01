# Sociopro

Legacy Laravel social application.

## Composer Commands

Run Composer scripts with `composer <script>` from the project root.

| Command | Description |
| --- | --- |
| `composer test` | Run the full Laravel test suite. |
| `composer test:unit` | Run PHPUnit's `Unit` testsuite. |
| `composer test:feature` | Run PHPUnit's `Feature` testsuite. |
| `composer pint` | Format PHP files with Laravel Pint. |
| `composer pint:test` | Check PHP formatting without writing changes. |
| `composer analyse` | Run PHPStan if `vendor/bin/phpstan` is installed; otherwise print a skip message. |
| `composer rector:dry` | Run Rector in dry-run mode if `vendor/bin/rector` is installed; otherwise print a skip message. |
| `composer rector` | Run Rector if `vendor/bin/rector` is installed; otherwise print a skip message. |
| `composer quality` | Run formatting checks, optional static analysis, and the full test suite. |
| `composer ci` | Run Composer validation, Composer audit, formatting checks, optional static analysis, and tests. |

Current installed PHP quality tools are PHPUnit and Laravel Pint. PHPStan and Rector are intentionally guarded so these scripts remain compatible before those tools are added.
