<?php
namespace Gongo\MercifulPolluter;

class Session extends Base
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
            if ($this->ignoringVariable($name)) {
                continue;
            }

            $GLOBALS[$name] = $value;
            $_SESSION[$name] =& $GLOBALS[$name];
        }
    }
}
