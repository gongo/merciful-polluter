<?php
namespace Gongo\MercifulPolluter\Test;

use PHPUnit\Framework\TestCase;
use Gongo\MercifulPolluter\Base;

class Polluter extends Base
{
    public function check($theKey)
    {
        return $this->ignoringVariable($theKey);
    }
}

class BaseTest extends TestCase
{
    private $object = null;

    protected function setUp()
    {
        $this->object = new Polluter;
    }

    public function testIgnoringVariable()
    {
        $ignores = array(
            'GLOBALS',
            '_SERVER',
            '_GET',
            '_POST',
            '_FILES',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            '_COOKIE',
            'php_errormsg',
            'HTTP_RAW_POST_DATA',
            'http_response_header',
            'argc',
            'argv',
        );

        foreach ($ignores as $key) {
            $this->assertTrue($this->object->check($key));
        }

        $throughs = array(
            '__FILE__',
            'foo',
            'bar',
            '_GETTT'
        );

        foreach ($throughs as $key) {
            $this->assertFalse($this->object->check($key));
        }
    }
}
