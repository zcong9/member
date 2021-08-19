<?php

namespace Common\Model;

class AccountLogModel extends \Common\Model\BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'account_log';

    /**
     * 删除状态 1是 0否
     */
    const DEL_YES = 1;
    const DEL_NO = 0;

    /**
     * 资金变动类型：0充值，1=充值退款，2订单消费，3订单退款，4转账
     */
    const CHANGE_TYPE_RECHARGE = 0;
    const CHANGE_TYPE_RECHARGE_REFUND = 1;
    const CHANGE_TYPE_ORDER_CONSUMPTION = 2;
    const CHANGE_TYPE_ORDER_REFUND = 3;
    const CHANGE_TYPE_TRANSFER_ACCOUNTS = 4;

    /**
     * 账号类型：1预存款
     */
    const ACCOUNT_TYPE_PRE_MONEY = 1;

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
        array('before_change_amount', 'integer', '金额错误', 0),
        array('after_change_amount', 'integer', '金额错误', 0),
        array('amount', 'integer', '金额错误', 0),
        array('change_type', array(self::CHANGE_TYPE_RECHARGE, self::CHANGE_TYPE_RECHARGE_REFUND, self::CHANGE_TYPE_ORDER_CONSUMPTION, self::CHANGE_TYPE_ORDER_REFUND, self::CHANGE_TYPE_TRANSFER_ACCOUNTS), '资金变动类型错误', 0, 'in'),
        array('account_type', array(self::ACCOUNT_TYPE_PRE_MONEY), '活动类型错误', 0, 'in'),
        array('desc', '0,255', '描述不能超过255个字符', 0, 'length'),
        array('fee', 'integer', '费率错误', 0),
        array('fee_amount', 'integer', '费率金额错误', 0),
        array('source_member_id', 'integer', '转账会员ID错误', 0),
        array('order_sn', 'require', '订单号必须'),
        array('transaction_sn', 'require', '交易单号必须'),
        array('from_ip', '0,32', 'Ip地址错误', 0, 'length'),
        array('sys_uid', 'integer', '管理员ID错误', 0),
        array('remark', '0,255', '备注不能超过255个字符', 0, 'length'),
        array('create_at', 'integer', '创建时间错误', 0),
        array('update_at', 'integer', '更新时间错误', 0),
        array('is_del', array(self::DEL_NO, self::DEL_YES), '状态错误', 0, 'in'),
    );

}

