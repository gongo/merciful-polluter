<?php

/**
 * Polyfill for PHPUnit 4.8
 */

namespace PHPUnit\Framework
{
    if (!class_exists('PHPUnit\\Framework\\TestCase')) {
        abstract class TestCase extends PHPUnit_Framework_TestCase
        {
        }
    } else {
        class_alias('PHPUnit\Framework\Error\Warning', 'PHPUnit_Framework_Error_Warning');
    }
}
