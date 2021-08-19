<?php

namespace Common\Extend;

class Log extends \Think\Log
{
    static function writeApi($message, $data = null, $level = self::ERR, $type = '', $destination = '')
    {
        if (!self::$storage) {
            $type = $type ?: C('LOG_TYPE');
            $class = 'Think\\Log\\Driver\\' . ucwords($type);
            $config['log_path'] = C('LOG_PATH');
            self::$storage = new $class($config);
        }
        if (empty($destination)) {
            $destination = C('LOG_PATH') . date('YmdH') . '.log';
        }
        $msg = "{$level}: {$message}" . PHP_EOL;
        $params = $_REQUEST;
        if (key_exists('thinkphp_show_page_trace', $params)) {
            unset($params['thinkphp_show_page_trace']);
        }
        if (key_exists('i18next', $params)) {
            unset($params['i18next']);
        }
        if (key_exists('__forward__', $params)) {
            unset($params['__forward__']);
        }
        if (key_exists('PHPSESSID', $params)) {
            unset($params['PHPSESSID']);
        }
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $input = $_POST;
                break;
            case 'PUT':
                parse_str(file_get_contents('php://input'), $_PUT);
                $input = $_PUT;
                break;
            default:
                $input = $_GET;
        }
        $body = $input ?: file_get_contents('php://input');
        $msg .= "[headers]: " . json_encode(self::header()) . PHP_EOL;
        $msg .= "[params]: " . json_encode($params) . PHP_EOL;
        $msg .= "[body]: " . (is_array($body) ? json_encode($body) : $body) . PHP_EOL;
        $msg .= "[result]: " . (is_array($data) ? json_encode($data) : $data) . PHP_EOL;
        self::$storage->write($msg, $destination);
    }

    private static function header()
    {
        $header = [];
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }
        return $header;
    }
}