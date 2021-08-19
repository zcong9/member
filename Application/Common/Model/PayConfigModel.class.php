<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/10/21
 * Time: 15:43
 */

namespace Common\Model;


class PayConfigModel extends \Common\Model\BaseModel
{
    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;

    const TYPE_WX = 1;//微信

    /**
     * 自动验证码
     * @var array
     */
    protected $_validate = array(
        array('status', [self::STATUS_DISABLE, self::STATUS_ENABLE], '状态错误', 2, 'in', 1),
        array('type', [self::TYPE_WX], '支付平台类型错误', 2, 'in', 1),
        array('merchant_id', 'number', '商户ID错误', 0),
        array('store_id', 'number', '门店ID错误', 0),
    );
}