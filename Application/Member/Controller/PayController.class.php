<?php

namespace Member\Controller;
use Common\Utils\Tools;

class PayController extends \Think\Controller
{
    public function __construct()
    {
        parent::__construct();

        //判断是否微信浏览器

        //判断是否登录
    }

    private function ToXml($returnMsg)
    {
        $xml = "<xml>";
        foreach ($returnMsg as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /*--------------预充值微信支付开始--------------*/
    public function wxPrepaid()
    {
        // 根据订单查询出vip_id，再查询出代理ID
        $prepaid_order = D("prepaid_order");
        $order_sn = I('order_sn/s');
        $orderInfo = $prepaid_order->where(array("order_sn" => $order_sn))->find();
        $vipModel = D("members");
        $vip_info = $vipModel->where(array("id" => $orderInfo['member_id']))->find();
        if (empty($vip_info)) exit('会员不存在');
        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $Out_trade_no = $order_sn;
        $restaurant_name = D("merchant")->where(array("merchant_id" => $orderInfo['merchant_id']))->getField("merchant_name");
        if ($restaurant_name) {
            $Body = $restaurant_name;
        } else {
            $Body = '方雅智慧点餐';
        }
        $Total_fee = (int)$orderInfo['total_amount'];
        
        //使用jsapi接口
        $jsApi = new \JsApi_pub();
        //=========步骤1：网页授权获取用户openid============
        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $url = $jsApi->createOauthUrlForCode("http://" . $_SERVER['HTTP_HOST'] . "/index.php/member/pay/wxPrepaid/order_sn/" . $order_sn);
            Header("Location: $url");
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }

        $ordinary = D("pay_mode")->where(['merchant_id'=>$orderInfo['merchant_id']])->getField("mode");
        if ($ordinary == "2") {
            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid", "{$openid}");
            $unifiedOrder->setParameter("body", $Body); //商品描述
            //自定义订单号，此处仅作举例
            $unifiedOrder->setParameter("out_trade_no", $Out_trade_no); //商户订单号
            $unifiedOrder->setParameter("total_fee", $Total_fee); //总金额
            $unifiedOrder->setParameter("notify_url", "http://" . $_SERVER['HTTP_HOST'] . "/index.php/member/pay/prepaid"); //通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型
        } else {
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("sub_openid", "$openid");
            $unifiedOrder->setParameter("body", $Body); //商品描述
            //自定义订单号，此处仅作举例
            $unifiedOrder->setParameter("out_trade_no", $Out_trade_no); //商户订单号
            $unifiedOrder->setParameter("total_fee", $Total_fee); //总金额

            $unifiedOrder->setParameter("notify_url", "http://" . $_SERVER['HTTP_HOST'] . "/index.php/member/pay/prepaid"); //通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型
            //非必填参数，商户可根据实际情况选填
            $unifiedOrder->setParameter("sub_appid", \WxPayConf_pub::$SUB_APPID); //子商户号
            $unifiedOrder->setParameter("sub_mch_id", \WxPayConf_pub::$SUB_MCHID); //子商户号
        }

        $prepay_id = $unifiedOrder->getPrepayId();

        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);

        $jsApiParameters = $jsApi->getParameters();
        $this->assign("jsApiParameters", $jsApiParameters);
        $this->display("pay");
    }

    //    # 对客户提交的预充值数据进行优惠处理
    public function prepaid()
    {
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        \Think\Log::write('充值订单回调：'.json_encode($notifyInfo,JSON_UNESCAPED_UNICODE));

        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。

            //操作数据库处理订单信息；
            $order_sn = $notifyInfo['out_trade_no'];
            $orderModel = D("prepaid_order");
            $o_condition['order_sn'] = $order_sn;
            $orderInfo = $orderModel->where($o_condition)->field("order_id,member_id,merchant_id,order_status,pay_time,account,benefit,finall_benefit")->find();
            
            if (empty($orderInfo)) {
                \Think\Log::write('订单异常：' . $notifyInfo['out_trade_no']);
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
                exit;
            }

            //更新订单状态
            if (0 != $orderInfo['order_status'] && ($orderInfo['pay_time'] > 0)) {
                //订单更新状态
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
                exit;
            }

            //当前支付模式
            $ordinary = D("pay_mode")->where(['merchant_id'=>$orderInfo['merchant_id']])->getField("mode");
            if($ordinary == "2"){
                $data['openid'] = (isset($notifyInfo['openid']) && !empty($notifyInfo['openid'])) ? $notifyInfo['openid'] : '';
            }else{
                $data['openid'] = (isset($notifyInfo['sub_openid']) && !empty($notifyInfo['sub_openid'])) ? $notifyInfo['sub_openid'] : '';
            }

            //更新订单状态
            $data['pay_time'] = time();
            $data['order_status'] = 1;
            $data['trade_num'] = $notifyInfo['transaction_id'];

            $mod = M();
            $money = $mod->table('members')->where(['id' => $orderInfo['member_id']])->getField('money');
            try {

                $mod->startTrans();

                $mod->table('prepaid_order')->where($o_condition)->save($data);

                //更新members money
                $mData['money'] = ['exp', 'money+' . $orderInfo['finall_benefit']];
                $mod->table('members')->where(['id' => $orderInfo['member_id']])->save($mData);

                $log['member_id'] = $orderInfo['member_id'];
                $log['merchant_id'] = $orderInfo['merchant_id'];
                $log['order_sn'] = $order_sn;
                $log['desc'] = "";
                $log['change_type'] = 1;
                $log['create_at'] = time();
                $log['amount'] = $orderInfo['finall_benefit'];
                $log['before_change_amount'] = (int)$money;
                $log['after_change_amount'] = bcadd($orderInfo['finall_benefit'], $money);
                $log['account_type'] = 1;
                $log['transaction_sn'] = Tools::uuid(2);
                $log['desc'] = "预存款充值";
                $mod->table('account_log')->add($log);

                //记录订单支付日志
                $logData = array(
                    'merchant_id' => $orderInfo['merchant_id'],
                    'order_sn' => $order_sn,
                    'pay_status' => 1,
                    'pay_amount' => bcdiv($notifyInfo['total_fee'], 100, 2),
                    'pay_mode' => intval($ordinary),
                    'pay_type' => 2,
                    'trade_num' => $notifyInfo['transaction_id'],
                    'log' => json_encode($notifyInfo)
                );
                (new \Common\Model\PayLogModel())->doAdd($logData);

                $mod->commit();

            } catch (\Exception $e) {
                $mod->rollback();
                \Think\Log::write('充值更新订单状态异常：' . $e->getMessage());
            }

            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            exit;

        } else {
            echo $this->ToXml(['code' => 0, 'return_code' => 'FAIL_1', 'return_msg' => 'SAVE DATA FAIL']);
        }
    }
}
