<?php

namespace Api\Services;

use Services\BaseServices;
use Common\Exceptions\ValidateException;
use Common\Model\OrdersModel;
use Common\Model\MembersModel;
use Common\Model\AccountLogModel;
use Common\Utils\Tools;
use Common\Model\RefundOrderModel;
use Common\Model\ConsumeCodeModel;

/**
 * 支付服务
 * Class PayServices
 * @package Services
 */
class PayServices extends BaseServices
{
    public function pay($post)
    {
        $ordersMod = new OrdersModel();
        $orderInfo = $ordersMod->get($post['order_id']);
        if (empty($orderInfo)) throw new ValidateException('订单ID：' . $post['order_id'] . '不存在', 0);
        if ($ordersMod::ORDER_STATUS_ALREADY == $orderInfo['order_status']) throw new ValidateException('订单已支付', 0);
        $membersMod = new MembersModel();
        $memberInfo = $membersMod->get($orderInfo['member_id']);
        $money = $memberInfo['money'];
        if (empty($memberInfo)) throw new ValidateException('会员账号异常', 0);
        if ($membersMod::STATUS_DISABLE == $memberInfo['status']) throw new ValidateException('会员账号已冻结', 0);
        if ($orderInfo['total_amount'] > $money) throw new ValidateException('余额不足', 0);

        $ConsumeCodeMod = new ConsumeCodeModel();
        if ($ConsumeCodeMod->get(['code'=>$post['code']])) throw new ValidateException('二维码已消费', 0);

        $m = M();
        try {

            $m->startTrans();

            $timestamp = time();
            $mData['money'] = ['exp', 'money-' . $orderInfo['total_amount']];
            $membersMod->where(['id' => $orderInfo['member_id']])->save($mData);

            //更新订单状态
            $ordersMod->where(['order_id' => $orderInfo['order_id']])
                ->save(['order_status' => 1, 'pay_time' => $timestamp, 'update_at' => $timestamp, 'pay_amount' => $orderInfo['total_amount']]);

            $accountLogMod = new AccountLogModel();
            //记录支付信息
            $log['member_id'] = $orderInfo['member_id'];
            $log['store_id'] = $orderInfo['store_id'];
            $log['merchant_id'] = $orderInfo['merchant_id'];
            $log['order_sn'] = $orderInfo['order_sn'];
            $log['change_type'] = $accountLogMod::CHANGE_TYPE_ORDER_CONSUMPTION;
            $log['create_at'] = $timestamp;
            $log['amount'] = -$orderInfo['total_amount'];
            $log['fee'] = 0;
            $log['fee_amount'] = 0;
            $log['before_change_amount'] = $money;
            $log['after_change_amount'] = bcadd($log['amount'], $money, 0);
            $log['account_type'] = $accountLogMod::ACCOUNT_TYPE_PRE_MONEY;
            $log['transaction_sn'] = Tools::uuid(2);
            $log['desc'] = "订单支付";
            $log['from_id'] = get_client_ip();

            $logId = $accountLogMod->edit($log);
            if (empty($logId)) {
                $m->rollback();
                throw new ValidateException($accountLogMod->getError(), 0);
            }

            //保存消费码凭证
            $ConsumeCodeMod->edit(array(
                'member_id' => $orderInfo['member_id'],
                'store_id' => $orderInfo['store_id'],
                'merchant_id' => $orderInfo['merchant_id'],
                'code' => $post['code'],
                'order_sn' => $orderInfo['order_sn'],
            ));

            $m->commit();

            $result = array(
                'order_id' => $orderInfo['order_id'],
                'member_id' => $orderInfo['member_id'],
                'order_sn' => $orderInfo['order_sn'],
                'out_trade_no' => $orderInfo['out_trade_no'],
                'pay_amount' => $orderInfo['total_amount']
            );

            return $result;

        } catch (\Exception $e) {

            \Think\Log::write("{$orderInfo['order_sn']}|订单扣款异常：" . $e->getMessage());
            $m->rollback();
            return false;

        }

    }

    public function refund($post)
    {
        $refundMod = new RefundOrderModel();
        $ordersMod = new OrdersModel();
        $refundOrderInfo = $refundMod->get($post['order_id']);
        if (empty($refundOrderInfo)) throw new ValidateException('订单ID：' . $post['order_id'] . '不存在', 0);
        if ($refundMod::REFUND_STATUS_ALREADY == $refundOrderInfo['status']) throw new ValidateException('订单已退款', 0);
        $membersMod = new MembersModel();
        $memberInfo = $membersMod->get($refundOrderInfo['member_id']);
        $money = $memberInfo['money'];
        if (empty($memberInfo)) throw new ValidateException('会员账号异常', 0);

        $m = M();
        try {

            $m->startTrans();

            $timestamp = time();
            $mData['money'] = ['exp', 'money+' . $refundOrderInfo['total_amount']];
            $membersMod->where(['id' => $refundOrderInfo['member_id']])->save($mData);

            //更新订单状态
            $ordersMod->where(['order_sn' => $refundOrderInfo['trade_sn']])
                ->save(['refund_status' => $ordersMod::REFUND_STATUS_ALREADY, 'refund_amount' => $refundOrderInfo['total_amount'], 'refund_at' => $timestamp]);

            $refundMod->where([$refundMod->getPk() => $refundOrderInfo['refund_id']])
                ->save(['refund_status' => $ordersMod::REFUND_STATUS_ALREADY, 'refund_amount' => $refundOrderInfo['total_amount'], 'update_at' => $timestamp]);

            $accountLogMod = new AccountLogModel();
            //记录支付信息
            $log['member_id'] = $refundOrderInfo['member_id'];
            $log['store_id'] = $refundOrderInfo['store_id'];
            $log['merchant_id'] = $refundOrderInfo['merchant_id'];
            $log['order_sn'] = Tools::uuid(2);
            $log['change_type'] = $accountLogMod::CHANGE_TYPE_ORDER_REFUND;
            $log['create_at'] = $timestamp;
            $log['amount'] = $refundOrderInfo['total_amount'];
            $log['fee'] = 0;
            $log['fee_amount'] = 0;
            $log['before_change_amount'] = $money;
            $log['after_change_amount'] = bcadd($log['amount'], $money, 0);
            $log['account_type'] = $accountLogMod::ACCOUNT_TYPE_PRE_MONEY;
            $log['transaction_sn'] = Tools::uuid(2);
            $log['desc'] = "订单退款";
            $log['from_id'] = get_client_ip();

            $logId = $accountLogMod->edit($log);
            if (empty($logId)) {
                $m->rollback();
                throw new ValidateException($accountLogMod->getError(), 0);
            }

            $m->commit();

            $result = array(
                'refund_sn' => $refundOrderInfo['refund_sn'],
                'out_trade_no' => $refundOrderInfo['out_trade_no'],
                'trade_sn' => $refundOrderInfo['trade_sn'],
                'refund_amount' => $refundOrderInfo['total_amount']
            );

            return $result;

        } catch (\Exception $e) {

            \Think\Log::write("{$refundOrderInfo['order_sn']}|订单退款异常：" . $e->getMessage());
            $m->rollback();
            return false;

        }

    }
}
