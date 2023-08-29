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

    public function testPollute()
    {
        $this->object = new Session;

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
     * Below annotations are for PHPUnit < 9.0
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage The session not yet started (Ignoring)
     */
    public function testPolluteSessionNotStarted()
    {
        // For PHPUnit >= 9.0
        if (method_exists($this, 'expectWarning')) {
            $this->expectWarning();
            $this->expectWarningMessage('The session not yet started (Ignoring)');
        }
        
        $this->object = new Session;
        $this->object->pollute();
    }

    /**
     * @see https://github.com/gongo/merciful-polluter/issues/2
     */
    public function testPolluteSpecifiedBlacklist()
    {
        $this->object = new Session;

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
