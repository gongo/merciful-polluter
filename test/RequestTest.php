<?php
namespace Gongo\MercifulPolluter\Test;

use Gongo\MercifulPolluter\Request;

/**
 * @backupGlobals enabled
 */
class RequestTest extends \Gongo\MercifulPolluter\Test\TestCase
{
    private $object = null;

    public function testPollute()
    {
        $this->setUpMethod();

        $_FILES['upfile'] = array(
            'tmp_name' => '/tmp/aqwsedrftgyhujik.txt',
            'name'     => 'test.txt',
            'type'     => 'text/plain',
            'size'     => 123
        );
        $_POST['article_id'] = 99999;
        $_GET['secret_info'] = array('address' => "'Okinawa'");
        $_COOKIE['session_id'] = 'SESSIONID';

        $this->setVariablesOrder('EGPCS');
        $this->object->pollute();

        global $upfile, $upfile_name, $upfile_type, $upfile_size;
        $this->assertEquals($_FILES['upfile']['tmp_name'], $upfile);
        $this->assertEquals($_FILES['upfile']['name'], $upfile_name);
        $this->assertEquals($_FILES['upfile']['type'], $upfile_type);
        $this->assertEquals($_FILES['upfile']['size'], $upfile_size);

        global $article_id;
        $this->assertEquals($_POST['article_id'], $article_id);

        global $secret_info;
        $this->assertEquals($_GET['secret_info'], $secret_info);

        global $session_id;
        $this->assertEquals($_COOKIE['session_id'], $session_id);
    }

    /**
     * <input type="file" name="music[]">
     * <input type="file" name="music[]">
     * <input type="file" name="movie">
     */
    public function testPollutePostMultipleFile()
    {
        $this->setUpMethod();

        $_FILES['music'] = array(
            'tmp_name' => array('/tmp/aqwerft', '/tmp/gyhujiko'),
            'size'     => array(123, 456)
        );

        $_FILES['movie'] = array(
            'tmp_name' => '/tmp/xdcfvgbhjn',
            'size'     => 789
        );

        $this->setVariablesOrder('EGPCS');
        $this->object->pollute();

        global $music, $music_size;
        $this->assertEquals($_FILES['music']['tmp_name'], $music);
        $this->assertEquals($_FILES['music']['size'], $music_size);

        global $movie, $movie_size;
        $this->assertEquals($_FILES['movie']['tmp_name'], $movie);
        $this->assertEquals($_FILES['movie']['size'], $movie_size);
    }

    public function testPolluteOverwriteVariableOrder()
    {
        $this->setUpMethod();

        $_GET['id'] = 'get';
        $_POST['id'] = 'post';

        $this->setVariablesOrder('gpg');
        $this->object->pollute();

        global $id;
        $this->assertEquals($_POST['id'], $id);
    }

    public function testPolluteOverwriteArrayToArray()
    {
        $this->setUpMethod();

        $_GET['foo'] = array('bar' => 'baz');
        $_POST['foo'] = array('spam' => 'ham');

        $this->setVariablesOrder('gp');
        $this->object->pollute();

        global $foo;
        $this->assertEquals(array('bar' => 'baz', 'spam' => 'ham'), $foo);
    }

    public function testPolluteOverwriteNestedArrayToNestedArray()
    {
        $this->setUpMethod();

        $_GET['foo'] = array('bar' => array('baz' => '123'));
        $_POST['foo'] = array('bar' => array('spam' => 'ham'));

        $this->setVariablesOrder('gp');
        $this->object->pollute();

        global $foo;
        $this->assertEquals(
            array(
                'bar' => array(
                    'baz' => '123',
                    'spam' => 'ham'
                )
            ),
            $foo
        );
    }

    public function testPolluteOverwriteScalarToArray()
    {
        $this->setUpMethod();

        $_GET['foo'] = 'bar';
        $_POST['foo'] = array('spam' => 'ham');

        $this->setVariablesOrder('gp');
        $this->object->pollute();

        global $foo;
        $this->assertEquals(array('spam' => 'ham'), $foo);
    }

    public function testPolluteOverwriteArrayToScalar()
    {
        $this->setUpMethod();

        $_GET['foo'] = array('bar' => 'baz');
        $_POST['foo'] = 'ham';

        $this->setVariablesOrder('gp');
        $this->object->pollute();

        global $foo;
        $this->assertEquals('ham', $foo);
    }

    public function testPolluteOverwriteArrayToScalarToArray()
    {
        $this->setUpMethod();

        $_GET['foo'] = array('bar' => 'baz');
        $_POST['foo'] = 'ham';
        $_COOKIE['foo'] = array('spam' => 'ham');

        $this->setVariablesOrder('gpc');
        $this->object->pollute();

        global $foo;
        $this->assertEquals(array('spam' => 'ham'), $foo);
    }

    public function testPolluteOverwriteArrayToArrayToScalar()
    {
        $this->setUpMethod();
        $_GET['foo'] = array('bar' => 'baz');
        $_POST['foo'] = array('spam' => 'ham');
        $_COOKIE['foo'] = 'ham';

        $this->setVariablesOrder('gpc');
        $this->object->pollute();

        global $foo;
        $this->assertEquals('ham', $foo);
    }

    public function testPolluteEnableMagicQuotesGpc()
    {
        $this->setUpMethod();

        $_ENV['TOKEN'] = "foo'bar";
        $_GET['name_1'] = "baz'piyo_get";
        $_GET['personal_info_1'] = array('address' => "'Okinawa'");
        $_POST['name_2'] = "baz'piyo_post";
        $_POST['personal_info_2'] = array('address' => "'Tokyo'");
        $_COOKIE['name_3'] = "baz'piyo_cookie";
        $_COOKIE['personal_info_3'] = array('address' => "'Kanagawa'");
        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);

        $this->setVariablesOrder('egpc');
        $this->object->enableMagicQuotesGpc();
        $this->object->pollute();

        global $TOKEN;
        $this->assertEquals("foo'bar", $TOKEN);
        $this->assertEquals("foo'bar", $_ENV['TOKEN']);

        global $name_1, $personal_info_1, $name_2, $personal_info_2, $name_3, $personal_info_3;
        $this->assertEquals("baz\'piyo_get", $name_1);
        $this->assertEquals("baz\'piyo_get", $_GET['name_1']);
        $this->assertEquals("\'Okinawa\'", $personal_info_1['address']);
        $this->assertEquals("\'Okinawa\'", $_GET['personal_info_1']['address']);

        $this->assertEquals("baz\'piyo_post", $name_2);
        $this->assertEquals("baz\'piyo_post", $_POST['name_2']);
        $this->assertEquals("\'Tokyo\'", $personal_info_2['address']);
        $this->assertEquals("\'Tokyo\'", $_POST['personal_info_2']['address']);

        $this->assertEquals("baz\'piyo_cookie", $name_3);
        $this->assertEquals("baz\'piyo_cookie", $_COOKIE['name_3']);
        $this->assertEquals("\'Kanagawa\'", $personal_info_3['address']);
        $this->assertEquals("\'Kanagawa\'", $_COOKIE['personal_info_3']['address']);

        $this->assertEquals("baz\'piyo_get", $_REQUEST['name_1']);
        $this->assertEquals("\'Okinawa\'", $_REQUEST['personal_info_1']['address']);
        $this->assertEquals("baz\'piyo_post", $_REQUEST['name_2']);
        $this->assertEquals("\'Tokyo\'", $_REQUEST['personal_info_2']['address']);
        $this->assertEquals("baz\'piyo_cookie", $_REQUEST['name_3']);
        $this->assertEquals("\'Kanagawa\'", $_REQUEST['personal_info_3']['address']);
    }

    /**
     * http://example.com/?foo=123&bar=baz&_GET[foo]=Cracked&_GET[bar]=Cracked
     *
     * @see https://github.com/gongo/merciful-polluter/issues/2
     */
    public function testPolluteSpecifiedBlacklist()
    {
        $this->setUpMethod();

        $_GET['foo']  = '123';
        $_GET['bar']  = 'baz';
        $_GET['_GET'] = array(
            'foo' => 'Cracked',
            'bar' => 'Cracked'
        );

        $this->setVariablesOrder('g');
        $this->object->pollute();

        global $foo;
        $this->assertEquals('123', $_GET['foo']);
        $this->assertEquals('123', $foo);

        global $bar;
        $this->assertEquals('baz', $_GET['bar']);
        $this->assertEquals('baz', $bar);
    }

    public function testPolluteNotReportByDefault()
    {
        $this->setUpMethod();

        $_GET['id'] = '1';

        $errored = false;
        set_error_handler(function () use (&$errored) {
            $errored = true;
        });

        $this->setVariablesOrder('g');
        $this->object->pollute();

        restore_error_handler();

        $this->assertFalse($errored);
    }

    public function testPolluteReportWithDefaultReporter()
    {
        $this->setUpMethod();

        $_SERVER['SCRIPT_NAME'] = '/admin/edit.php';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_ENV['TOKEN'] = 'secret';
        $_FILES['upfile'] = array('tmp_name' => '/tmp/aqwsedrftgyhujik.txt');
        $_GET['id'] = '1';
        $_POST["it's"] = '2';

        $errors = array();
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = array($errno, $errstr);
        });

        $this->setVariablesOrder('egpcs');
        $this->object->enableReport();
        $this->object->pollute();

        restore_error_handler();

        $this->assertEquals(
            array(
                array(
                    E_USER_DEPRECATED,
                    "MercifulPolluter: 2 variables injected in /admin/edit.php (may depend on register_globals): "
                    . "\$upfile from \$_FILES['upfile']['tmp_name'], \$id from \$_GET['id']"
                ),
            ),
            $errors
        );
    }

    public function testPolluteReportWithDefaultReporterLimitsList()
    {
        $this->setUpMethod();

        unset($_SERVER['SCRIPT_NAME']);
        for ($i = 1; $i <= 12; $i++) {
            $_GET["a{$i}"] = '1';
        }

        $errors = array();
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;
        });

        $this->setVariablesOrder('g');
        $this->object->enableReport();
        $this->object->pollute();

        restore_error_handler();

        $this->assertCount(1, $errors);
        $this->assertStringStartsWith(
            "MercifulPolluter: 12 variables injected (may depend on register_globals): \$a1 from \$_GET['a1'], ",
            $errors[0]
        );
        $this->assertStringEndsWith("\$a10 from \$_GET['a10'] and 2 more", $errors[0]);
    }

    public function testPolluteReportWithDefaultReporterSkipsInvalidNames()
    {
        $this->setUpMethod();

        $_SERVER['SCRIPT_NAME'] = '/index.php/<script>alert(1)</script>';
        $_GET["a\nPHP Fatal error: forged"] = '1';
        $_GET['<script>alert(1)</script>'] = '1';
        $_GET['id'] = '1';

        $errors = array();
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;
        });

        $this->setVariablesOrder('g');
        $this->object->enableReport();
        $this->object->pollute();

        restore_error_handler();

        $this->assertEquals(
            array(
                "MercifulPolluter: 1 variable injected (may depend on register_globals): \$id from \$_GET['id']"
            ),
            $errors
        );
    }

    public function testPolluteReportWithDefaultReporterCountsLongNames()
    {
        $this->setUpMethod();

        unset($_SERVER['SCRIPT_NAME']);
        $_GET['id'] = '1';
        $_GET[str_repeat('a', 64)] = '1';
        $_GET[str_repeat('b', 65)] = '1';

        $errors = array();
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;
        });

        $this->setVariablesOrder('g');
        $this->object->enableReport();
        $this->object->pollute();

        restore_error_handler();

        $longest = str_repeat('a', 64);
        $this->assertEquals(
            array(
                "MercifulPolluter: 3 variables injected (may depend on register_globals): "
                . "\$id from \$_GET['id'], \${$longest} from \$_GET['{$longest}']; "
                . "1 with names longer than 64 bytes (use setReporter() to see them)"
            ),
            $errors
        );
    }

    /**
     * Long names alone must not look like "nothing was injected".
     */
    public function testPolluteReportWithDefaultReporterCountsOnlyLongNames()
    {
        $this->setUpMethod();

        unset($_SERVER['SCRIPT_NAME']);
        $_GET[str_repeat('a', 65)] = '1';

        $errors = array();
        set_error_handler(function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;
        });

        $this->setVariablesOrder('g');
        $this->object->enableReport();
        $this->object->pollute();

        restore_error_handler();

        $this->assertEquals(
            array(
                "MercifulPolluter: 1 variable injected (may depend on register_globals): "
                . "1 with names longer than 64 bytes (use setReporter() to see them)"
            ),
            $errors
        );
    }

    public function testPolluteReportWithDefaultReporterIgnoresServerAndEnv()
    {
        $this->setUpMethod();

        $_SERVER['HTTP_HOST'] = 'example.com';
        $_ENV['TOKEN'] = 'secret';

        $errored = false;
        set_error_handler(function () use (&$errored) {
            $errored = true;
        });

        $this->setVariablesOrder('es');
        $this->object->enableReport();
        $this->object->pollute();

        restore_error_handler();

        $this->assertFalse($errored);
    }

    /**
     * An error handler converting E_USER_DEPRECATED to an exception must not stop the injection halfway.
     */
    public function testPolluteReportAfterAllVariablesInjected()
    {
        $this->setUpMethod();

        $_GET['first'] = '1';
        $_POST['second'] = '2';

        set_error_handler(function ($errno, $errstr) {
            throw new \RuntimeException($errstr);
        });

        $this->setVariablesOrder('gp');
        $this->object->enableReport();

        $thrown = null;
        try {
            $this->object->pollute();
        } catch (\RuntimeException $e) {
            $thrown = $e;
        }

        restore_error_handler();

        $this->assertNotNull($thrown);

        global $first, $second;
        $this->assertEquals('1', $first);
        $this->assertEquals('2', $second);
    }

    public function testPolluteReportWithCustomReporter()
    {
        $this->setUpMethod();

        $_FILES['upfile'] = array(
            'tmp_name' => '/tmp/aqwsedrftgyhujik.txt',
            'size'     => 123
        );
        $_GET['id'] = 'get';
        $_GET['_GET'] = array('id' => 'Cracked');
        $_POST['id'] = 'post';
        $_POST["it's"] = 'quoted';
        $_SERVER = array('HTTP_HOST' => 'example.com');

        $calls = array();
        $this->setVariablesOrder('gps');
        $this->object->setReporter(function (array $injectedVariables) use (&$calls) {
            $calls[] = $injectedVariables;
        });
        $this->object->pollute();

        $this->assertEquals(
            array(
                array(
                    array('name' => 'upfile', 'origin' => "\$_FILES['upfile']['tmp_name']"),
                    array('name' => 'upfile_size', 'origin' => "\$_FILES['upfile']['size']"),
                    array('name' => 'id', 'origin' => "\$_GET['id']"),
                    array('name' => 'id', 'origin' => "\$_POST['id']"),
                    array('name' => "it's", 'origin' => "\$_POST['it\\'s']"),
                    array('name' => 'HTTP_HOST', 'origin' => "\$_SERVER['HTTP_HOST']"),
                ),
            ),
            $calls
        );
    }

    public function testPolluteReportFromSubclassCallingInjectToGlobal()
    {
        $object = new RequestCallingInjectToGlobal;

        $calls = array();
        $object->setReporter(function (array $injectedVariables) use (&$calls) {
            $calls[] = $injectedVariables;
        });
        $object->polluteCustom(array('custom' => 'value'));

        global $custom;
        $this->assertEquals('value', $custom);
        $this->assertEquals(
            array(
                array(
                    array('name' => 'custom', 'origin' => null),
                ),
            ),
            $calls
        );
    }

    public function testPolluteFilesThroughOverriddenInjectToGlobal()
    {
        $_FILES = array('upfile' => array('tmp_name' => '/tmp/aqwsedrftgyhujik.txt', 'size' => 123));
        unset($GLOBALS['upfile'], $GLOBALS['upfile_size']);

        $object = new RequestSkippingSize;
        $object->pollute();

        $this->assertEquals('/tmp/aqwsedrftgyhujik.txt', $GLOBALS['upfile']);
        $this->assertArrayNotHasKey('upfile_size', $GLOBALS);
    }

    public function testPolluteDisableReport()
    {
        $this->setUpMethod();

        $_GET['id'] = '1';

        $reported = array();
        $this->setVariablesOrder('g');
        $this->object->setReporter(function (array $injectedVariables) use (&$reported) {
            $reported[] = $injectedVariables;
        });
        $this->object->disableReport();
        $this->object->pollute();

        $this->assertEquals(array(), $reported);
    }

    private function setUpMethod()
    {
        $this->object = $this->createMockWithMethods(
            'Gongo\MercifulPolluter\Request',
            array('getInjectVariables')
        );
    }

    private function setVariablesOrder($value)
    {
        $this->object->method('getInjectVariables')
                     ->willReturn(str_split(strtolower($value)));
    }
}

/**
 * A subclass calling `injectToGlobal()` directly, as existing users may do.
 */
class RequestCallingInjectToGlobal extends Request
{
    public function polluteCustom(array $variables)
    {
        $this->injectToGlobal($variables);
        $this->flushReport();
    }
}

/**
 * A subclass overriding `injectToGlobal()`, as existing users may do.
 */
class RequestSkippingSize extends Request
{
    protected function injectToGlobal(array $theVariables)
    {
        unset($theVariables['upfile_size']);
        parent::injectToGlobal($theVariables);
    }

    protected function getInjectVariables()
    {
        return array();
    }
}
