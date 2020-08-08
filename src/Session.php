<?php
namespace Gongo\MercifulPolluter;

class Session extends Base
{
    /**
     * @return void
     */
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

    /**
     * @return void
     */
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
