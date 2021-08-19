<?php

namespace Api\Services;

use Common\Exceptions\AuthException;
use Common\Utils\Tools;
use Common\Model\AuthConfigModel;

/**
 * 认证服务
 * Class AuthServices
 * @package Services
 */
class AuthServices
{
    var $config = [];

    /**
     * AuthServices constructor.
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config,$config);
    }

    public function checkSign($data)
    {
        if (!$this->checkParams($data)) throw new AuthException('必要参数缺失', 10001);
        $info = $this->getKeyInfo($data['appid']);
        if (empty($info)) throw new AuthException('appid not found', 10005);
        $this->config['secret'] = $info['secret'];
        if ($data['sign'] !== $this->makeSign($data)) throw new AuthException('签名校验失败', 10002);
        $eTime = C('API_REQUEST_AUTH_TIME') ? (intval(C('API_REQUEST_AUTH_TIME')) * 1000) : 2 * 60 * 1000;
        if ((Tools::get13TimeStamp() - $data['timestamp']) > $eTime) throw new AuthException('签名失效', 10003);
        return $info;
    }

    private function checkParams($data)
    {
        if ((!isset($data['timestamp']) || empty($data['timestamp']))
            || (!isset($data['appid']) || empty($data['appid']))
            || (!isset($data['sign']) || empty($data['sign']))
            || (!isset($data['version']) || empty($data['version']))
            || (!isset($data['nonce']) || empty($data['nonce']))
        ) return false;
        return true;
    }

    /**
     * 生成签名
     * @param $data
     * @return string
     */
    public function makeSign($data)
    {
        if (isset($data['sign'])) unset($data['sign']);
        $dataStr = (isset($data['data']) && !empty($data['data'])) ? json_encode($data['data'], JSON_UNESCAPED_UNICODE) : '';
        $str = $data['appid'] . $data['nonce'] . $this->config['secret'] . $data['timestamp'] . $data['version'] . $dataStr;
        return strtoupper(md5($str));
    }

    /**
     * 账号权限认证
     * @param $appid
     * @return string
     */
    public function getKeyInfo($appid){
        $mod = new AuthConfigModel();
        return $mod->get(['appid'=>$appid]);
    }

}
