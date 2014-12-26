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

    public function testPolluteOverwriteVariableOrder()
    {
        $_GET['id'] = 'get';
        $_POST['id'] = 'post';
        
        $this->setVariablesOrder('gpg');
        $this->object->pollute();

        global $id;
        $this->assertEquals($_POST['id'], $id);
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
    
    private function setVariablesOrder($value)
    {
        $this->object->method('getInjectVariables')
                     ->willReturn(str_split(strtolower($value)));
    }
}
