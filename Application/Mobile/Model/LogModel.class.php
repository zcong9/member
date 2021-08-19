<?php

namespace Mobile\Model;

use Think\Model;

class LogModel
{
    public function __construct()
    {

    }

    public function errorLog($msg, $title = '', $dir = './log/errorLog/')
    {
        file_put_contents($this->getLogFileName($dir), $this->getLogContent($msg, $title), 8);
    }

    public function successLog($msg, $title = '', $dir = './log/success.log')
    {
        file_put_contents($this->getLogFileName($dir), $this->getLogContent($msg, $title), 8);
    }

    private function getLogFileName($dir)
    {
        return getcwd() . $dir . date('Y-m-d') . '.log';
    }

    private function getLogContent($msg, $title)
    {
        return date('Y-m-d H:i:s') . '-' . $title . ': ' . $msg . "\r\n\r\n";
    }
}