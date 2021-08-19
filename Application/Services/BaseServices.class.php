<?php
namespace Services;

/**
 * Class BaseServices
 * @package Services
 */
abstract class BaseServices
{
    /**
     * 模型注入
     * @var object
     */
    public $mod;

    const LIMIT = 10;

    private $mchId = 0;
    private $storeId = 0;
    private $memberId = 0;

    public function setMchId($mchId)
    {
        $this->mchId = $mchId;
        return $this;
    }

    public function getMchId()
    {
        return $this->mchId;
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;
        return $this;
    }

    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        return call_user_func_array([$this->mod, $name], $arguments);
    }
}
