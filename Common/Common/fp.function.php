<?php
/**
 * 下划线转驼峰
 * @param $uncamelized_words
 * @param string $separator
 * @return string
 */
function camelize($uncamelized_words, $separator = '_')
{
    $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));
    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
}

/**
 * 驼峰命名转下划线命名
 * @param $camelCaps
 * @param string $separator
 * @return string
 */
function uncamelize($camelCaps, $separator = '_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

/**
 * 注册中止时执行的函数
 * @param callable $func
 */
function register_shutdown_func(callable $func) {
    register_shutdown_function($func);
}

/**
 * 响应后继续处理耗时的任务
 * @param callable $func
 */
function finish_request_func(callable $func){
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    $func();
}

/**
 * 获取13位时间戳
 * @return int
 */
function timestamp_13() {
    list($tmp1, $tmp2) = explode(' ', microtime());
    return sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
}

/**
 * log id
 * @return int
 */
function log_id(){
    return ((microtime(true) * 100000) & 0x7FFFFFFF);
}

/**
 * 返回消息
 */
if (defined('IS_API')) {
    function message($msg = "系统繁忙，请稍候再试" ,$code = 90000 , $data = array()){
        $msg =  array("code" => $code , "msg" => $msg , "data" => $data, "logId"=> log_id(), "serverTime"=>timestamp_13());
        return $msg;
    }
}else{
    function message($msg = "操作成功" , $code = 1 , $data = array()){
        $msg =  array("code" => $code , "msg" => $msg , "data" => $data);
        return $msg;
    }
}

/**
 * 返回域名
 */
function domain()
{
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 1 || $_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] == 443)) {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    return $_SERVER['HTTP_HOST'] ? $protocol . $_SERVER['HTTP_HOST'] : C('DOMAIN');
}

/**
 * 数组转json内容
 * @param array $data
 * @return null|string
 */
function arr2json($data)
{
    header("Content-type: application/json");
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    $json=str_replace("null",'""',$json);
    return $json === '[]' ? '{}' : $json;
}

/**
 * 写入数据
 * @param string $path
 * @param $fileName
 * @param $str
 */
function wFile($path = '',$fileName = '',$str)
{
    if (is_array($str)) {
        $str = json_encode($str,JSON_UNESCAPED_UNICODE);
    }
    if ($path === null || $path == ''){
        $path = getcwd().'/logs/';
    }
    if ($fileName === null || $fileName == '') {
        $fileName = date('YmdHis').'.log';
    }
    file_exists($path) || mkdir($path, 0755, true);
    @file_put_contents($path . $fileName, $str. "\n", FILE_APPEND);
}

/**
 * 下载文件
 * 可以指定下载显示的文件名，并自动发送相应的Header信息
 * 如果指定了content参数，则下载该参数的内容
 * @param string $filename 下载文件名
 * @param string $showname 下载显示的文件名
 * @param string $content  下载的内容
 * @param integer $expire  下载内容浏览器缓存时间
 * @return void
 */
function download($filename, $showname = '', $content = '', $expire = 180)
{
    if (is_file($filename)) {
        $length = filesize($filename);
    } elseif (is_file(UPLOAD_PATH . $filename)) {
        $filename = UPLOAD_PATH . $filename;
        $length = filesize($filename);
    } elseif ($content != '') {
        $length = strlen($content);
    } else {
        E($filename . L('下载文件不存在！'));
    }
    if (empty($showname)) {
        $showname = $filename;
    }
    $showname = basename($showname);
    $type = "application/octet-stream";
    //发送Http Header信息 开始下载
    header("Pragma: public");
    header("Cache-control: max-age=" . $expire);
    //header('Cache-Control: no-store, no-cache, must-revalidate');
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
    header("Content-Disposition: attachment; filename=" . $showname);
    header("Content-Length: " . $length);
    header("Content-type: " . $type);
    header('Content-Encoding: none');
    header("Content-Transfer-Encoding: binary");
    if ($content == '') {
        readfile($filename);
    } else {
        echo($content);
    }
    exit();
}

/**
 * request请求
 * @param $path
 * @param string $method
 * @param array $param
 * @param string $url
 */
function doRequest($path, $method = "POST", $param = array(),$url = '')
{
    $query = isset($param) ? http_build_query($param) : '';
    if($url) {
        $matches = parse_url($url);
        $host = $matches['host'];
        if ($matches['scheme'] == 'https') {
            $transports = 'ssl://';
            $port = !empty($matches['port']) ? $matches['port'] : 443;
        } else {
            $transports = 'tcp://';
            $port = !empty($matches['port']) ? $matches['port'] : 80;
        }
    }else{
        $port = 443;
        $transports = 'ssl://';
        $host = $_SERVER['HTTP_HOST'];
    }
    $errno = 0;
    $errstr = '';
    $timeout = 30;
    $fp = fsockopen($transports . $host, $port, $errno, $errstr, $timeout);
    stream_set_blocking($fp, 0);
    $out = "{$method} " . $path . " HTTP/1.1\r\n";
    $out .= "host:" . $host . "\r\n";
    $out .= "content-length:" . strlen($query) . "\r\n";
    $out .= "content-type:application/x-www-form-urlencoded\r\n";
    $out .= "connection:close\r\n\r\n";
    $out .= $query;
    fputs($fp, $out);
    fclose($fp);
}