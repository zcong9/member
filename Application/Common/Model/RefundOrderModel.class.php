<?php

namespace Common\Model;

class RefundOrderModel extends \Common\Model\BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'refund_id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'refund_order';

    /**
     * 删除状态 1是 0否
     */
    const DEL_YES = 1;
    const DEL_NO = 0;


    /**
     * 状态：0待退款，1已退款
     */
    const REFUND_STATUS_WAIT = 0;
    const REFUND_STATUS_ALREADY = 1;

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
        array('total_amount', 'integer', '金额错误', 0),
        array('refund_amount', 'integer', '金额错误', 0),
        array('refund_sn', 'require', '退款订单号必须'),
        array('trade_sn', 'require', '交易单号必须', 0),
        array('out_trade_no', 'require', '商户订单号必须', 0),
        array('out_refund_no', 'require', '商户退款单号必须', 0),
        array('remarks', '0,255', '备注不能超过255个字符', 0, 'length'),
        array('refund_status', array(self::REFUND_STATUS_WAIT, self::REFUND_STATUS_ALREADY), '退款状态错误', 0, 'in'),
        array('create_at', 'integer', '创建时间错误', 0),
        array('update_at', 'integer', '更新时间错误', 0),
        array('is_del', array(self::DEL_NO, self::DEL_YES), '状态错误', 0, 'in'),
    );
}

