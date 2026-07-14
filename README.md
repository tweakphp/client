# TweakPHP Client

TweakPHP Client is a PHAR used by [TweakPHP](https://github.com/tweakphp/tweakphp)
to execute PHP code inside a project.

## Requirements

- PHP 7.4 or higher

## Build

Build the PHAR with production dependencies only:

```bash
make build
```
> The build removes `vendor`, installs the project dependencies with `--no-dev`,
> installs the pinned Box version in a temporary Composer home, and creates
> `client.phar`. Development dependencies are not included in the PHAR.

## Command Syntax

The project directory is always required and must come before the command:

```bash
php client.phar <project-directory> <command> [base64-code]
```

For the current directory, use `.` or `$PWD`:

```bash
php client.phar . info
php client.phar "$PWD" info
```

Available commands:

```bash
php client.phar <project-directory> info
php client.phar <project-directory> execute <base64-code>
php client.phar <project-directory> execute-stream <base64-code>
```

## Check Project Info

`info` reports the detected project type, project version, and PHP version:

```bash
php client.phar /path/to/project info
```

Example response:

```json
{"name":"Laravel","version":"11.x","php_version":"8.4.0"}
```

## Execute Code

The PHP code must be Base64-encoded before it is passed to the client.

```bash
code=$(printf '%s' 'echo "Hello";' | base64)
php client.phar /path/to/project execute "$code"
```

The command waits until the complete script has finished and returns one JSON
result prefixed with `TWEAKPHP_RESULT:`:

```text
TWEAKPHP_RESULT:{"output":[{"line":2,"code":"echo \"Hello\";","output":"Hello","queries":[]}],"queries":[]}
```

If the command fails, it returns `TWEAKPHP_ERROR:` and exits with status `1`:

```text
TWEAKPHP_ERROR:{"class":"InvalidArgumentException","message":"Invalid Base64-encoded PHP code."}
```

User code calling `exit()` or `die()` preserves the requested exit status.
Instrumentation failures are reported in the optional `query_errors` field rather
than being silently discarded.

Multiple statements are supported:

```bash
code=$(printf '%s' 'echo "First"; sleep(1); echo "Second";' | base64)
php client.phar "$PWD" execute "$code"
```

## Execute As A Stream

Use `execute-stream` when the caller needs output while the script is running.
The project directory is still required:

```bash
code=$(printf '%s' 'echo "First"; sleep(1); echo "Second";' | base64)
php client.phar "$PWD" execute-stream "$code"
```

The command writes one JSON event per line, each prefixed with
`TWEAKPHP_STREAM:`:

```text
TWEAKPHP_STREAM:{"type":"statement.started","index":0,"line":2,"code":"echo \"First\";"}
TWEAKPHP_STREAM:{"type":"output","index":0,"data":"First"}
TWEAKPHP_STREAM:{"type":"statement.completed","index":0,"queries":[]}
TWEAKPHP_STREAM:{"type":"completed"}
```

Event types:

- `statement.started`: a statement started
- `output`: the statement produced output
- `statement.completed`: a statement finished, with collected queries
- `error`: a statement or the client failed; the process exits with status `1`,
  or with the status requested by `exit()`/`die()`
- `completed`: all statements finished

An `exit()` or `die()` call emits an `error` event with its exit status and the
process exits with that status.

## Supported Projects

The client detects the project type automatically:

- Laravel: `vendor/autoload.php` and `bootstrap/app.php`
- Symfony: `vendor/autoload.php`, `symfony.lock`, and `src/Kernel.php`
- WordPress: `wp-load.php`
- Pimcore: `vendor/pimcore/pimcore`
- Composer: `vendor/autoload.php`
- Plain PHP: any directory not matching a more specific project type

Examples:

```bash
# Laravel
code=$(printf '%s' 'return App\Models\User::query()->latest()->first();' | base64)
php client.phar /path/to/laravel execute "$code"

# WordPress
code=$(printf '%s' 'return get_option("blogname");' | base64)
php client.phar /path/to/wordpress execute "$code"

# Plain PHP or Composer
code=$(printf '%s' 'return PHP_VERSION;' | base64)
php client.phar /path/to/project execute "$code"
```
