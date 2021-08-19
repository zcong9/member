<?php

namespace Common\Model;

class PayLogModel extends \Common\Model\BaseModel
{
    const DEL_YES = 1;
    const DEL_NO = 0;

    const PAY_TYPE_ALI = 1;//支付宝
    const PAY_TYPE_WX = 2;//微信

    const PAY_MODE_OFFICIAL_PRO = 1;//官方服务商支付
    const PAY_MODE_OFFICIAL_OY = 2;//官方普通支付

    //自动完成
    protected $_auto = array(
        array('create_at', 'time', 1, 'function'),
    );

    /**
     * 新增
     * @param $data
     * @return string
     */
    public function doAdd($data){
        try {
            $data = $this->create($data);
            $data && $this->add($data);
            return $this->getLastInsID();
        } catch (\Exception $e) {
            return false;
        }
    }
}

