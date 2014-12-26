<?php
namespace Gongo\MercifulPolluter;

class Session
{
    public function pollute()
    {
        if (session_id() === '') {
            trigger_error(
                'The session not yet started (Ignoring)',
                E_USER_WARNING
            );
            return;
        }

        $this->injectToGlobal($_SESSION);
    }

    protected function injectToGlobal(array $theVariables)
    {
        foreach ($theVariables as $name => $value) {
            $GLOBALS[$name] = $value;
            $_SESSION[$name] =& $GLOBALS[$name];
        }
    }
}
