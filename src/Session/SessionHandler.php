<?php
namespace Pofol\Session;

use Exception;
use SessionHandlerInterface;

class SessionHandler implements SessionHandlerInterface
{
    protected $path;

    public function __construct($session_path = null)
    {
        $this->path = $session_path;
    }

    public function open($save_path, $name)
    {
        if ($this->path === null) {
            $this->path = $save_path;
        }

        if (!is_dir($this->path)) {
            throw new Exception("{$this->path} 폴더가 존재하지 않습니다.");
        }

        return true;
    }

    public function read($session_id)
    {
        $filepath = "{$this->path}\\$session_id";

        if (!file_exists($filepath)) {
            $this->write($session_id, '');
            return '';
        }

        $filesize = filesize($filepath);

        if ($filesize === 0) {
            return '';
        }

        $fp = fopen($filepath, 'r');
        $contents = fread($fp, $filesize);
        fclose($fp);

        return $contents;
    }

    public function write($session_id, $session_data)
    {
        $filepath = "{$this->path}\\$session_id";

        $fp = fopen($filepath, 'w');
        fwrite($fp, $session_data);
        fclose($fp);
    }

    public function close()
    {
        // 세션이 끝날 때 수행할 작업
    }

    public function destroy($session_id)
    {
        $filepath ="{$this->path}\\$session_id";

        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function gc($maxlifetime)
    {
        // Garbage Collection에 의해 실행될 때
        // $maxlifetime의 단위는 s
    }
}
