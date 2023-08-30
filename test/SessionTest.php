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
     * NOTE: Why not use `@expectedException` or `$this->expectWarning()` ?
     *
     * Expecting E_WARNING and E_USER_WARNING is deprecated and will no longer be possible in PHPUnit 10.
     *
     * @see https://phpunit.de/announcements/phpunit-10.html
     * @see https://codeseekah.com/2023/03/01/testing-warnings-in-phpunit-9/
     */
    public function testPolluteSessionNotStarted()
    {
        $errored = null;
        set_error_handler(function($errno, $errstr) use (&$errored) {
            $errored = [$errno, $errstr];
            restore_error_handler();
        });
        
        $this->object = new Session;
        $this->object->pollute();

        [$errno, $errstr] = $errored;
        $this->assertEquals(E_USER_WARNING, $errno);
        $this->assertEquals('The session not yet started (Ignoring)', $errstr);
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
