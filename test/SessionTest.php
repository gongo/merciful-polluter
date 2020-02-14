<?php
namespace Gongo\MercifulPolluter\Test;

use PHPUnit\Framework\TestCase;
use Gongo\MercifulPolluter\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionTest extends TestCase
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

    /**
     * @see https://github.com/gongo/merciful-polluter/issues/2
     */
    public function testPolluteSpecifiedBlacklist()
    {
        session_start();

        $_SESSION['_GET'] = '1234';
        $_SESSION['_POST'] = array('userId', 'Evil');
        $_SESSION['userId'] = 'Jack';

        $this->object->pollute();

        $this->assertNotEquals($_SESSION['_GET'], $_GET);
        $this->assertNotEquals($_SESSION['_POST'], $_POST);

        global $userId;
        $this->assertEquals('Jack', $userId);
    }
}
