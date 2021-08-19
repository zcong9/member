<?php

namespace Api\Controller;

class BaseController extends \Think\Controller
{
    protected $mchId;
    protected $storeId;
    protected $memberId;
    protected $params;

    public function __construct()
    {
        $this->_auth();
    }

    private function _auth()
    {
        $this->params = getInputData(file_get_contents('php://input'));
        $auth = (new \Api\Services\AuthServices())->checkSign($this->params);
        $this->mchId = $auth['merchant_id'];
        if (isset($this->params['data']['store_id'])) {
            (new \Api\Services\StoreServices())->checkStoreIdBindMchId((int)$this->params['data']['store_id'], $this->mchId);
            $this->storeId = (int)$this->params['data']['store_id'];
        }
    }

    /**
     * 记录日志
     * @param string $msg
     */
    public static function log($msg)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        $path = dirname($_SERVER['SCRIPT_FILENAME']).'/logs/api/'.strtolower(CONTROLLER_NAME).'/';
        file_exists($path) || mkdir($path, 777, true);
        @file_put_contents($path . date('YmdH') . '.log', date('Y-m-d H:i:s') . ':' . $msg. "\n", FILE_APPEND);
    }

    public function __destruct()
    {
        $msg = $this->params;
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        $path = dirname($_SERVER['SCRIPT_FILENAME']).'/logs/request/api/'.strtolower(CONTROLLER_NAME).'/'.strtolower(ACTION_NAME).'/';
        file_exists($path) || mkdir($path, 777, true);
        @file_put_contents($path . date('YmdH') . '.log', date('Y-m-d H:i:s') . ':' . $msg. "\n", FILE_APPEND);
        parent::__destruct();
    }

}