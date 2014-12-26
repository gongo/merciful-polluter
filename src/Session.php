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
}
