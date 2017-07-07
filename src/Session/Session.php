<?php
namespace Pofol\Session;

use Pofol\PofolService\PofolService;

class Session implements PofolService
{
    public function boot()
    {
        $configPath = __PF_ROOT__ . '\\' . config('session.PATH');
        session_write_close();
        session_set_save_handler(new SessionHandler($configPath));
        session_start();
    }
}
