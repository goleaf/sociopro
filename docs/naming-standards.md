# Naming Standards

Generated: 2026-07-02

## PHP Classes And Files

- PHP class names must use StudlyCase / PascalCase.
- PHP class files must match the class name exactly.
- Underscores are not allowed in PHP class names.
- Underscores are not allowed in PHP class-backed file names, including models, controllers, requests, resources, policies, services, jobs, listeners, notifications, factories, and tests.
- Database tables and columns may still use lowercase snake_case when that is the persisted schema contract.

## Examples

- Wrong: `Message_thrade`
- Correct: `MessageThread`
- Wrong file: `Message_thrade.php`
- Correct file: `MessageThread.php`

The `MessageThread` model intentionally maps to the legacy `message_thrades` table so the PHP naming can be fixed without a database table rename.
