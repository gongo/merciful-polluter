MercifulPolluter
====================

To relieve [register_globals](http://php.net/register_globals) and [magic_quotes_gpc](http://php.net/magic_quotes_gpc) refugees.

[![Build Status](https://travis-ci.org/gongo/merciful-polluter.svg?branch=master)](https://travis-ci.org/gongo/merciful-polluter)
[![Coverage Status](https://coveralls.io/repos/gongo/merciful-polluter/badge.png?branch=master)](https://coveralls.io/r/gongo/merciful-polluter?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gongo/merciful-polluter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gongo/merciful-polluter/?branch=master)

Description
--------------------

In PHP 5.4, violent directives (`register_globals` and `magic_quotes_gpc`) has been removed.
But system that depends on them still running lot maybe.

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
$request = new Gongo\MercifulPolluter\Reqeust;
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
