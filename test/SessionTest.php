<?php
namespace Gongo\MercifulPolluter\Test;

use \PHPUnit_Framework_TestCase;
use Gongo\MercifulPolluter\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionTest extends PHPUnit_Framework_TestCase
{
    private $object = null;
    
    protected function setUp()
    {
        $this->object = new Session;
    }

    public function testPollute()
    {
        session_start();
        
        $_SESSION['userId'] = '1234';
        $_SESSION['userName'] = 'Jack';

        $this->object->pollute();

        global $userId, $userName;
        $this->assertEquals($_SESSION['userId'], $userId);
        $this->assertEquals($_SESSION['userName'], $userName);

        // Reference global to session
        $userId = '99999';
        $this->assertEquals('99999', $_SESSION['userId']);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage The session not yet started (Ignoring)
     */
    public function testPolluteSessionNotStarted()
    {
        $this->object->pollute();
    }
}
