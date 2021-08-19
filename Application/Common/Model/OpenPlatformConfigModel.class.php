<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/10/21
 * Time: 15:43
 */

namespace Common\Model;


class OpenPlatformConfigModel extends \Common\Model\BaseModel
{
    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;

    /**
     * 订单同步
     * 0=不同步，1=同步
     */
    const ORDER_PUSH_DISABLE = 0;
    const ORDER_PUSH_ENABLE = 1;

    const TYPE_KUANYI = 1;//款易

    /**
     * 自动验证码
     * @var array
     */
    protected $_validate = array(
        array('status', [self::STATUS_DISABLE, self::STATUS_ENABLE], '状态错误', 2, 'in', 1),
        array('is_order_push', [self::ORDER_PUSH_DISABLE, self::ORDER_PUSH_ENABLE], '订单同步状态错误', 2, 'in', 1),
        array('type', [self::TYPE_KUANYI], '开放平台类型错误', 2, 'in', 1),
        array('business_id', 'number', '代理ID错误', 0),
        array('restaurant_id', 'number', '店铺ID错误', 0),
    );
}