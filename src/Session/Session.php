<?php
namespace Pofol\Session;

class Session
{
    public function get($key = null, $value = null)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return $value;
    }
}