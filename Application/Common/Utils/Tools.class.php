<?php
namespace Common\Utils;

class Tools
{
    /**
     * 获取uuid
     * @param int $suffix_len
     * @return string
     */
    public static function uuid($suffix_len = 3){
        //! 计算种子数的开始时间
        static $being_timestamp = 1483200000;// 2017-01-01 00:00:00
        $time = explode(' ', microtime());
        $id = ($time[1] - $being_timestamp) . sprintf('%06u', substr($time[0], 2, 6));
        if ($suffix_len > 0) $id .= substr(sprintf('%010u', mt_rand()), 0, $suffix_len);
        return $id;
    }

    /**
     * 多表分页范围值计算
     * @param array $tabMap
     * @param int $pageNum
     * @param int $pageSize
     * @return array
     */
    public static function multiTablePaging($tabMap = array(), $pageNum = 1, $pageSize = 10)
    {
        $idx = [($pageSize * ($pageNum - 1)), (($pageSize * $pageNum) - 1)];
        $map = [];
        $num = 0;
        $p = 0;
        foreach ($tabMap as $tab => $n) {
            if (!$n) continue;
            $num += $n;
            if (($idx[0] <= $num) && ($idx[1] >= $num)) {
                $sidx = ($idx[0] < $pageSize) ? 0 : $idx[0] - $p;
                $eidx = ($idx[0] < $pageSize) ? $n : $n - $sidx;
                $map[] = ['tab' => $tab, 'limit' => "{$sidx},{$eidx}"];
            }

            if ($idx[1] <= $num) {
                $sidx = ($idx[0] < $pageSize) || ($p > $idx[0]) ? 0 : $idx[0] - $p;
                $eidx = empty($map) ? $pageSize : $pageSize - ($p % $pageSize);
                $map[] = ['tab' => $tab, 'limit' => "{$sidx},{$eidx}"];
                break;
            }

            $p += $n;
        }
        return $map;
    }

    /**
     * 获取两个时间戳之间的年月 格式：Ym
     * @param $sTimestamp
     * @param $eTimestamp
     * @return array
     */
    public static function getMonthsByBetweenTwoTimestamp($sTimestamp, $eTimestamp)
    {
        $monMap[] = date('Ym', $sTimestamp);
        while (($sTimestamp = strtotime('+1 month', $sTimestamp)) <= $eTimestamp) {
            $monMap[] = date('Ym', $sTimestamp);
        }
        return array_unique($monMap);
    }

    /**
     * 获取13位时间戳
     * @return int
     */
    public static function get13TimeStamp() {
        list($tmp1, $tmp2) = explode(' ', microtime());
        return sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
    }

    /**
     * 数组转XML内容
     * @param array $data
     * @return string
     */
    public static function arr2xml($data)
    {
        return "<xml>" . self::_arr2xml($data) . "</xml>";
    }

    /**
     * XML内容生成
     * @param array $data 数据
     * @param string $content
     * @return string
     */
    private static function _arr2xml($data, $content = '')
    {
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = 'item';
            $content .= "<{$key}>";
            if (is_array($val) || is_object($val)) {
                $content .= self::_arr2xml($val);
            } elseif (is_string($val)) {
                $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
            } else {
                $content .= $val;
            }
            $content .= "</{$key}>";
        }
        return $content;
    }

    /**
     * 解析XML内容到数组
     * @param string $xml
     * @return array
     */
    public static function xml2arr($xml)
    {
        $entity = libxml_disable_entity_loader(true);
        $data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_disable_entity_loader($entity);
        return json_decode(json_encode($data), true);
    }

    /**
     * 解析XML文本内容
     * @param string $xml
     * @return boolean|mixed
     */
    public static function xml3arr($xml)
    {
        $state = xml_parse($parser = xml_parser_create(), $xml, true);
        return xml_parser_free($parser) && $state ? self::xml2arr($xml) : false;
    }

    /**
     * 数组转json内容
     * @param array $data
     * @return null|string
     */
    public static function arr2json($data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $json = str_replace("null",'""',$json);
        $json = preg_replace('/[\x00-\x1F]/','', $json);
        return $json === '[]' ? '{}' : $json;
    }

    /**
     * 解析JSON内容到数组
     * @param string $json
     * @return array
     */
    public static function json2arr($json)
    {
        return json_decode(preg_replace('/[\x00-\x1F]/','', $json),true);
    }

    /**
     * 数组对象Emoji编译处理
     * @param array $data
     * @return array
     */
    public static function buildEnEmojiData(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::buildEnEmojiData($value);
            } elseif (is_string($value)) {
                $data[$key] = self::emojiEncode($value);
            } else {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * 数组对象Emoji反解析处理
     * @param array $data
     * @return array
     */
    public static function buildDeEmojiData(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::buildDeEmojiData($value);
            } elseif (is_string($value)) {
                $data[$key] = self::emojiDecode($value);
            } else {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * Emoji原形转换为String
     * @param string $content
     * @return string
     */
    public static function emojiEncode($content)
    {
        return json_decode(preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($string) {
            return addslashes($string[0]);
        }, json_encode($content)));
    }

    /**
     * Emoji字符串转换为原形
     * @param string $content
     * @return string
     */
    public static function emojiDecode($content)
    {
        return json_decode(preg_replace_callback('/\\\\\\\\/i', function () {
            return '\\';
        }, json_encode($content)));
    }



    /**
     * 以get访问模拟访问
     * @param string $url 访问URL
     * @param array $query GET数
     * @param array $options
     * @return boolean|string
     * @throws \Exception
     */
    public static function get($url, $query = [], $options = [])
    {
        $options['query'] = $query;
        return self::doRequest('get', $url, $options);
    }

    /**
     * 以post访问模拟访问
     * @param string $url 访问URL
     * @param array $data POST数据
     * @param array $options
     * @return boolean|string
     * @throws \Exception
     */
    public static function post($url, $data = [], $options = [])
    {
        $options['data'] = $data;
        return self::doRequest('post', $url, $options);
    }

    /**
     * CURL模拟网络请求
     * @param string $method 请求方法
     * @param string $url 请求方法
     * @param array $options 请求参数[headers,data,ssl_cer,ssl_key]
     * @param bool $debug 是否调试
     * @return boolean|string
     * @throws \Exception
     */
    public static function doRequest($method, $url, $options = [],$debug = false)
    {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($options['data'])) {
                    $tmpdatastr = is_array($options['data']) ? http_build_query($options['data']) : $options['data'];
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if($ssl){
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/

        if( count($options['headers']) >= 1 ){
            curl_setopt($ci, CURLOPT_HTTPHEADER, $options['headers']);
        }

        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($options['data']);
            echo "=====info===== \r\n";
            print_r($requestinfo);
            echo "=====response=====\r\n";
            print_r($response);
        }
        if ($response) {
            curl_close($ci);
            return $response;
        } else {
            $error = curl_errno($ci);
            curl_close($ci);
            throw new \Exception("curl出错，错误码:$error");
        }
        //return array($http_code, $response,$requestinfo);
    }
}
