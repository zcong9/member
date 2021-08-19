<?php

namespace Api\Services;

use Services\BaseServices;
use Common\Exceptions\ValidateException;
use Common\Utils\Tools;
use Common\Model\OrdersModel;
use Common\Model\RefundOrderModel;

/**
 * 订单服务
 * Class OrdersServices
 * @package Services
 */
class OrdersServices extends BaseServices
{
    public function create($post)
    {
        $this->mod = new OrdersModel();
        if (!(int)$post['total_amount']) throw new ValidateException('支付金额错误', 0);
        if ($this->mod->get(['out_trade_no' => $post['out_trade_no']])) throw new ValidateException('第三方订单号已存在', 0);
        $post['merchant_id'] = $this->getMchId();
        $post['store_id'] = $this->getStoreId();
        $post['member_id'] = $this->getMemberId();
        $post['order_sn'] = Tools::uuid();
        $post['pay_amount'] = 0;
        $post['refund_amount'] = 0;
        $post['order_status'] = ($this->mod)::ORDER_STATUS_WAIT;
        $orderId = $this->mod->edit($post);
        if (!$orderId) throw new ValidateException($this->mod->getError(), 0);
        return $orderId;
    }

    public function refund($post)
    {
        if (!(int)$post['total_amount']) throw new ValidateException('退款金额错误', 0);
        $ordersMod = new OrdersModel();
        if (isset($post['trade_sn']) && !empty($post['trade_sn'])) {
            $orderInfo = $ordersMod->get(['merchant_id' => $this->getMchId(), 'order_sn' => $post['trade_sn']]);
        }
        if (empty($orderInfo) && isset($post['out_trade_no']) && !empty($post['out_trade_no'])) {
            $orderInfo = $ordersMod->get(['merchant_id' => $this->getMchId(), 'out_trade_no' => $post['out_trade_no']]);
        }
        if (empty($orderInfo)) throw new ValidateException('订单不存在', 0);
        if ($orderInfo['order_status'] == $ordersMod::ORDER_STATUS_WAIT) throw new ValidateException('订单未支付', 0);
        if ($orderInfo['refund_status'] > $ordersMod::REFUND_STATUS_WAIT) throw new ValidateException('订单已退款', 0);
        if ($post['total_amount'] > $orderInfo['pay_amount']) throw new ValidateException('退款金额不能大于支付金额', 0);
        $refundMod = new RefundOrderModel();
        $post['merchant_id'] = $this->getMchId();
        $post['store_id'] = $orderInfo['store_id'];
        $post['member_id'] = $orderInfo['member_id'];
        $post['refund_sn'] = Tools::uuid();
        $post['out_trade_no'] = $orderInfo['out_trade_no'];
        $post['out_refund_no'] = $post['out_refund_no'];
        $post['refund_amount'] = 0;
        $post['status'] = $refundMod::REFUND_STATUS_WAIT;
        $post['is_del'] = $refundMod::DEL_NO;
        $post['remarks'] = (isset($post['remarks']) && !empty($post['remarks'])) ? $post['remarks'] : '';
        $refundId = $refundMod->edit($post);
        if (!$refundId) throw new ValidateException($refundMod->getError(), 0);
        return $refundId;
    }

}
