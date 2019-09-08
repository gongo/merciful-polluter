<?php
namespace Gongo\MercifulPolluter\Test;

use \PHPUnit_Framework_TestCase;
use Gongo\MercifulPolluter\Request;

class RequestTest extends PHPUnit_Framework_TestCase
{
    private $object = null;

    protected function setUp()
    {
        $this->object = $this->getMockBuilder('Gongo\MercifulPolluter\Request')
                             ->setMethods(array('getInjectVariables'))
                             ->getMock();
    }

    public function testPollute()
    {
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
        $_GET['id'] = 'get';
        $_POST['id'] = 'post';

        $this->setVariablesOrder('gpg');
        $this->object->pollute();

        global $id;
        $this->assertEquals($_POST['id'], $id);
    }

    public function testPolluteOverwriteArrayToArray()
    {
        $_GET['foo'] = array('bar' => 'baz');
        $_POST['foo'] = array('spam' => 'ham');

        $this->setVariablesOrder('gp');
        $this->object->pollute();

        global $foo;
        $this->assertEquals(array('bar' => 'baz', 'spam' => 'ham'), $foo);
    }

    public function testPolluteOverwriteNestedArrayToNestedArray()
    {
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
        $_GET['foo'] = 'bar';
        $_POST['foo'] = array('spam' => 'ham');

        $this->setVariablesOrder('gp');
        $this->object->pollute();

        global $foo;
        $this->assertEquals(array('spam' => 'ham'), $foo);
    }

    public function testPolluteOverwriteArrayToScalar()
    {
        $_GET['foo'] = array('bar' => 'baz');
        $_POST['foo'] = 'ham';

        $this->setVariablesOrder('gp');
        $this->object->pollute();

        global $foo;
        $this->assertEquals('ham', $foo);
    }

    public function testPolluteOverwriteArrayToScalarToArray()
    {
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
        $_ENV['TOKEN'] = "foo'bar";
        $_GET['secret_id'] = "baz'piyo";
        $_GET['secret_info'] = array('address' => "'Okinawa'");

        $this->setVariablesOrder('eg');
        $this->object->enableMagicQuotesGpc();
        $this->object->pollute();

        global $TOKEN;
        $this->assertEquals("foo'bar", $TOKEN);
        $this->assertEquals("foo'bar", $_ENV['TOKEN']);

        global $secret_id, $secret_info;
        $this->assertEquals("baz\'piyo", $secret_id);
        $this->assertEquals("baz\'piyo", $_GET['secret_id']);
        $this->assertEquals("\'Okinawa\'", $secret_info['address']);
        $this->assertEquals("\'Okinawa\'", $_GET['secret_info']['address']);
    }

    /**
     * http://example.com/?foo=123&bar=baz&_GET[foo]=Cracked&_GET[bar]=Cracked
     *
     * @see https://github.com/gongo/merciful-polluter/issues/2
     */
    public function testPolluteSpecifiedBlacklist()
    {
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

    private function setVariablesOrder($value)
    {
        $this->object->method('getInjectVariables')
                     ->willReturn(str_split(strtolower($value)));
    }
}
