<?php
namespace Pofol\Session;

use Pofol\PofolService\PofolService;

class SessionService implements PofolService
{
    public function boot()
    {
        $configPath = __PF_ROOT__ . '\\' . config('session.PATH');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name(config('session.NAME', 'Pofol'));

        session_set_save_handler(new SessionHandler($configPath));
        session_start();
    }
}
