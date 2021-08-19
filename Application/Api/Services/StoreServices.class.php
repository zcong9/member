<?php

namespace Api\Services;

use Services\BaseServices;
use Common\Exceptions\ValidateException;
use Common\Model\StoreModel;

/**
 * 店铺服务
 * Class StoreServices
 * @package Services
 */
class StoreServices extends BaseServices
{
    public function __construct()
    {
        $this->mod = new StoreModel();
    }

    public function checkStoreIdBindMchId($storeId, $mcdId)
    {
        if (!($this->mod->get([$this->mod->getPk() => $storeId, 'merchant_id' => $mcdId]))) throw new ValidateException('门店服务异常', 10010);
    }
}
