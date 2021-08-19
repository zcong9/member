<?php

namespace Common\Model;

class OrdersModel extends \Common\Model\BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'order_id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'orders';

    /**
     * 删除状态 1是 0否
     */
    const DEL_YES = 1;
    const DEL_NO = 0;


    /**
     * 订单状态：0待支付，1已支付
     */
    const ORDER_STATUS_WAIT = 0;
    const ORDER_STATUS_ALREADY = 1;

    /**
     * 退款状态：0待退款，1部分退款，2全额退款
     */
    const REFUND_STATUS_WAIT = 0;
    const REFUND_STATUS_PART = 1;
    const REFUND_STATUS_ALREADY = 2;

    /**
     * 自动完成
     * @var array
     */
    protected $_auto = array();

    /**
     * 自动验证码
     * @var array
     */
    protected $_validate = array(
        array('store_id', 'integer', '门店ID错误', 0),
        array('merchant_id', 'integer', '商户ID错误', 0),
        array('member_id', 'integer', '会员ID错误', 0),
        array('total_amount', 'integer', '支付金额错误', 0),
        array('pay_amount', 'integer', '实际支付金额错误', 0),
        array('order_sn', 'require', '订单号必须'),
        array('out_trade_no', 'require', '第三方单号必须', 0),
        array('refund_amount', 'integer', '退款金额错误', 0),
        array('pau_status', array(self::ORDER_STATUS_WAIT, self::ORDER_STATUS_ALREADY), '订单状态错误', 0, 'in'),
        array('refund_status', array(self::REFUND_STATUS_WAIT, self::REFUND_STATUS_PART, self::REFUND_STATUS_ALREADY), '退款状态错误', 0, 'in'),
        array('create_at', 'integer', '创建时间错误', 0),
        array('update_at', 'integer', '更新时间错误', 0),
        array('pay_time', 'integer', '支付时间错误', 0),
        array('is_del', array(self::DEL_NO, self::DEL_YES), '状态错误', 0, 'in'),
    );
}

