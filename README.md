MercifulPolluter
====================

To relieve [register_globals](http://php.net/register_globals) and [magic_quotes_gpc](http://php.net/magic_quotes_gpc) refugees.

![CI status](https://github.com/gongo/merciful-polluter/actions/workflows/ci.yml/badge.svg?branch=master)
[![Coverage Status](https://coveralls.io/repos/gongo/merciful-polluter/badge.png?branch=master)](https://coveralls.io/r/gongo/merciful-polluter?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gongo/merciful-polluter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gongo/merciful-polluter/?branch=master)

Description
--------------------

In PHP 5.4, violent directives (`register_globals` and `magic_quotes_gpc`) has been removed.
But there are systems that may still depend on them.

This library reproduce these features in PHP 5.4 and later. **Salvation**

Usage
--------------------

```php
<?php
/**
 * example.com/?foo=3&bar=12
 */

(new Gongo\MercifulPolluter\Request)->pollute();

global $foo, $bar;
var_dump($foo, $bar);

/**
 * int(3)
 * int(12)
 */
```

Installation
--------------------

Add this lines to your application's `composer.json`:

```json
{
    "require": {
        "gongo/merciful-polluter": "*"
    }
}
```

And then execute:

```sh
$ composer install
```

Or install it yourself as:

```sh
$ composer require gongo/merciful-polluter
```

See also: https://packagist.org/packages/gongo/merciful-polluter

Features
--------------------

### Emulate `register_globals`

Add this lines to your application's entry point (like in `auto_prepend_file`):

```php
<?php
$request = new Gongo\MercifulPolluter\Request;
$request->pollute();

// main routine...
```

If use the session, call `Gongo\MercifulPolluter\Session::pollute()` **after** `session_start()`:

```php
session_start();

/**
 * $_SESSION['user_id'] = 'gongo'
 */

(new Gongo\MercifulPolluter\Session)->pollute();

var_dump($user_id); // string(5) "gongo"

// Reference global variables to session.
$user_id = 'taro';
var_dump($_SESSION['user_id']); // string(4) "taro"
```

### Report injected variables

This library is a stopgap. To find out which variables are injected on your application,
call `enableReport()` before `pollute()`. The injected variables are reported as one `E_USER_DEPRECATED` per `pollute()`:

```php
<?php
/**
 * example.com/admin/edit.php?id=3&name=foo
 */

$request = new Gongo\MercifulPolluter\Request;
$request->enableReport();
$request->pollute();

// PHP Deprecated: MercifulPolluter: 2 variables injected in /admin/edit.php (may depend on register_globals): $id from $_GET['id'], $name from $_POST['name']
```

- The report is triggered after all variables are injected.
- Variables injected from `$_SERVER` and `$_ENV` are not reported by `enableReport()`, because they are too many.
- Variables with invalid names (e.g. `<script>`) are not reported by `enableReport()`, because the names come from users and your code cannot read them as `$name`.
- Variables with names longer than 64 bytes are only counted (e.g. `1 with names longer than 64 bytes`), so that huge names cannot flood your logs.
- Up to 10 variables are listed in a message (e.g. `... and 5 more`).

If you see `... with names longer than 64 bytes`, your application may read a variable with a long name.
Write your own reporter with `setReporter()` (see below) to find out the names, for example:

```php
<?php
$request->setReporter(function (array $injectedVariables) use ($logger) {
    foreach ($injectedVariables as $variable) {
        if (strlen($variable['name']) > 64) {
            // The name comes from users: cut it and escape control characters before logging.
            $name = addcslashes(substr($variable['name'], 0, 256), "\0..\37\177..\377");
            $logger->info('Long variable was injected: $' . $name);
        }
    }
});
```

To send reports to your own logger, use `setReporter()`. The callable is called once per `pollute()` with all injected variables (including `$_SERVER` and `$_ENV`).
The names are passed as they are, so escape them before writing them to logs or screens:

```php
<?php
$request = new Gongo\MercifulPolluter\Request;
$request->setReporter(function (array $injectedVariables) use ($logger) {
    foreach ($injectedVariables as $variable) {
        // $variable['name'] => 'id', $variable['origin'] => "$_GET['id']"
        $name = addcslashes($variable['name'], "\0..\37\177..\377");
        $origin = addcslashes((string)$variable['origin'], "\0..\37\177..\377");
        $logger->info("\${$name} was injected from {$origin}");
    }
});
$request->pollute();
```

The same methods are available on `Gongo\MercifulPolluter\Session` (e.g. `origin` => `"$_SESSION['user_id']"`).

Note that an injected variable is not always read by your code.
To find the code that actually depends on `register_globals`, static analysis (e.g. detecting undefined variables in the global scope with PHPStan) is more reliable.

### Emulate `magic_quotes_gpc`

If want to apply `magic_quotes_gpc`, call `Gongo\MercifulPolluter\Request::enableMagicQuotesGpc()`:

```php
<?php
/**
 * example.com/?foo=1'2'3
 */

$request = new Gongo\MercifulPolluter\Request;
$request->enableMagicQuotesGpc();
$request->pollute();

// $_GET['foo'] => "1\'2\'3"
//        $foo  => "1\'2\'3"
```

License
--------------------

MIT License.
