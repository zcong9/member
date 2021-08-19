<?php
namespace Mobile\Controller;
use Think\Controller;

class WxPayController extends Controller
{
    private $i = 0;
    public function pay()
    {
        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $order_sn                  = I('order_sn');
        $orderModel                = order();
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo                 = $orderModel->where($ono_condition)->find();
        session('restaurant_id', $orderInfo['restaurant_id']);
        session('desk_code', $orderInfo['desk_code']);
        $this->assign("order_sn", $order_sn);

        $restaurant_id = $orderInfo['restaurant_id'];

        //查询店铺餐桌二维码对应集成打印机的机器的机器码
        $qrc_code_model                 = D("qrc_code");
        $qrc_condition['restaurant_id'] = $restaurant_id;
        $qrc_code_id                    = $qrc_code_model->where($qrc_condition)->field("qrc_code_id")->find()['qrc_code_id'];
        $qrc_device_model               = D("qrc_device");
        $qrcd_condition['qrc_code_id']  = $qrc_code_id;
        $device_code                    = $qrc_device_model->where($qrcd_condition)->field('qrc_device_code')->find()['qrc_device_code'];

        $Out_trade_no    = $order_sn;
        $restaurant_name = D("restaurant")->where(array("restaurant_id" => $orderInfo['restaurant_id']))->getField("restaurant_name");
        if ($restaurant_name) {
            $Body = $restaurant_name;
        } else {
            $Body = '方雅智慧点餐';
        }
        $Total_fee = $orderInfo['total_amount'] * 100;

        //使用jsapi接口
        $jsApi = new \JsApi_pub();

        //=========步骤1：网页授权获取用户openid===========
        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $url = $jsApi->createOauthUrlForCode(\WxPayConf_pub::JS_API_CALL_URL . "/order_sn/" . $order_sn);
            Header("Location: $url");
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }

        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $where['restaurant_id'] = session("restaurant_id");
        $ordinary               = D("ordinary_pay")->where($where)->getField("open");
        if ($ordinary == "1") {
//普通用户
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid", "$openid");
            $unifiedOrder->setParameter("body", $Body); //商品描述
            //自定义订单号，此处仅作举例
            $unifiedOrder->setParameter("out_trade_no", $Out_trade_no); //商户订单号
            $unifiedOrder->setParameter("total_fee", $Total_fee); //总金额
            if ($device_code) {
                $unifiedOrder->setParameter("attach", $device_code); //机器码
            }
            $unifiedOrder->setParameter("notify_url", "http://shop.founpad.com/index.php/mobile/WxPay/notify"); //通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型
        } else {
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("sub_openid", "$openid");
            $unifiedOrder->setParameter("body", $Body); //商品描述
            //自定义订单号，此处仅作举例
            $unifiedOrder->setParameter("out_trade_no", $Out_trade_no); //商户订单号
            $unifiedOrder->setParameter("total_fee", $Total_fee); //总金额
            if ($device_code) {
                $unifiedOrder->setParameter("attach", $device_code); //机器码
            }
            $unifiedOrder->setParameter("notify_url", "http://shop.founpad.com/index.php/mobile/WxPay/notify"); //通知地址
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
        $this->display();
    }


    /**
     * 支付通知处理
     * @return type
     */
    public function notify()
    {
        header('Content-Type:text/xml; charset=utf-8');
        $postStr    = file_get_contents("php://input");
//        tunnelSend('wechat_pay_notify_xml： ' . $postStr);
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {

            //操作数据库处理订单信息；
            $order_sn                   = $notifyInfo['out_trade_no'];
            $orderModel                 = order();
            $o_condition['order_sn']    = $order_sn;
            $orderInfo                  = $orderModel->where($o_condition)->field("order_status,pay_time,restaurant_id,take_num")->find();
            $order_status               = $orderInfo['order_status'];
            $pay_time                   = $orderInfo['pay_time'];
//            $restaurantId               = $_SESSION['restaurant_id'];
            $restaurantId               = $orderInfo['restaurant_id'];
            $takeNum                    = $orderInfo['take_num'];
//            $order_status = 0;
//            $pay_time = 0;
            if ($order_status >= 3 && $pay_time > 0) {
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
                exit;
            } else {
                $data['order_status'] = 3;
                $data['pay_type']     = 2;

                //查询支付方式
                $ordinary  = M("pay_mode")->where(['restaurant_id'=>$restaurantId])->getField("mode");

                if($ordinary == "2"){
                    $data['openid']       = $notifyInfo['openid'];
                }else{
                    $data['openid']       = $notifyInfo['sub_openid'];
                }
//                $data['openid']       = $notifyInfo['openid'];
                $time                 = time();
                $data['pay_time']     = $time;
                $rel                  = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                if ($rel !== false) {
//                    $this->i++;
//                    $returnData['status']   = 2;
//                    $returnData['order_sn'] = $order_sn;
//                    $device_code            = $notifyInfo['attach'];
//
//                    $rel2 = sendInfo($returnData, $device_code);
//                    if ($rel2['errmsg'] == "Succeed") {
//                        $txt = $this->i;
//                        $txt .= "|" . $order_sn . "|" . date("Y-m-d H:i:s", time()) . "\r\n";
//                        file_put_contents(__DIR__ . "log.txt", $txt, FILE_APPEND);
//                    }

                    // 售罄处理
                    $S_SellOut = new ServiceSellOut();
                    $checkSellOutDeal = $S_SellOut->sellOutDeal($order_sn);

//                    //查询推送模式然后进行推送
//                    $where['restaurant_id'] = $restaurantId;
//                    $pushType               = M('restaurant')->where($where)->getField('push_type');
                    //等于2：核销屏、叫号屏推送
//                    if($pushType == 2){
//                        $push = $this->push($order_sn,$restaurantId,$takeNum);
//                    }elseif ($pushType == 3){//等于3：取餐柜推送
//
//                    }else{//其余普通模式不用推送
//
//                    }

                    //推送核销屏、叫号屏
                    $push = $this->push($order_sn,$restaurantId,$takeNum);
                    //打印后厨小票
                    $iotPushPrint = $this->iotPushPrint($restaurantId,$order_sn);
                }else{
                    tunnelSend('公众号微信支付回调失败，错误->：修改订单状态失败');
                    #失败
                    echo $this->ToXml(['code'=>0,'return_code' => 'FAIL_2', 'return_msg' => 'SAVE DATA FAIL']);
                }
            }
            # 所有操作成功，返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['code'=>1,'return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);

        }else{
            #失败
            tunnelSend('公众号微信支付回调失败，错误->：'.$postStr);
            echo $this->ToXml(['code'=>0,'return_code' => 'FAIL_1', 'return_msg' => 'SAVE DATA FAIL']);
        }
    }

    public function ToXml($returnMsg)
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
        $order_sn      = I('order_sn');
        $orderInfo     = $prepaid_order->where(array("order_sn" => $order_sn))->find();
        $vipModel      = D("members");
        $vip_info      = $vipModel->where(array("id" => $orderInfo['member_id']))->find();
        // 还要传递一个openid过去回调函数处进行处理
        $vip_openid = $vip_info["openid"]; // 写成vip_openid是为了跟下面微信支付接口的openid区分

        // 将区分从cofig表还是wx_prepaid_config表获取对接信息的标识存进session
        // session("wx_prepaid_flag", I("get.wx_prepaid_flag"));

        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        session("USER.mchId", 1);
        $Out_trade_no = $order_sn;
//        $Body='方雅智慧点餐';
        $merchant_name = D("merchant")->where(array("merchant_id" => $vip_info['merchant_id']))->getField("merchant_name");
        if ($merchant_name) {
            $Body = $merchant_name;
        } else {
            $Body = '方雅智慧点餐';
        }
    
        // $Total_fee = $orderInfo['total_amount'];
        $Total_fee = 1;

        //使用jsapi接口
        $jsApi = new \JsApi_pub();
   
        //=========步骤1：网页授权获取用户openid============
        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $url = $jsApi->createOauthUrlForCode("http://".$_SERVER['HTTP_HOST']."/index.php/mobile/WxPay/wxPrepaid/order_sn/" . $order_sn);
            Header("Location: $url");
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }
  
        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", "{$vip_info["openid"]}");
        $unifiedOrder->setParameter("body", $Body); //商品描述
        //自定义订单号，此处仅作举例
        $unifiedOrder->setParameter("out_trade_no", $Out_trade_no); //商户订单号
        $unifiedOrder->setParameter("total_fee", $Total_fee); //总金额
        if ($device_code) {
            $unifiedOrder->setParameter("attach", $device_code); //机器码
        }
        $unifiedOrder->setParameter("notify_url", "http://vip.cloudabull.com/index.php/mobile/WxPay/prepaid"); //通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型

        $prepay_id = $unifiedOrder->getPrepayId();
        
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);

        $jsApiParameters = $jsApi->getParameters();
        dump($jsApiParameters);exit;
        $this->assign("jsApiParameters", $jsApiParameters);
        $this->display("pay");
    }
    
    //    # 对客户提交的预充值数据进行优惠处理
    public function prepaid(){
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。
            file_put_contents(__DIR__."/"."MobileMemberPrepaidCallback.txt",$notifyInfo['out_trade_no']."\r\n\r\n",FILE_APPEND);

            //操作数据库处理订单信息；
            $order_sn = $notifyInfo['out_trade_no'];
            $orderModel = D("prepaid_order");
            $o_condition['order_sn'] = $order_sn;
            $orderInfo = $orderModel->where($o_condition)->field("order_id,member_id,order_status,pay_time,account,benefit,finall_benefit")->find();
            
            $vipInfo = M("vip")->where(array("id"=>$orderInfo['member_id']))->field("id,restaurant_id")->find();
            if((isset($orderInfo['order_status']) && $orderInfo['order_status'] == 0) && (isset($orderInfo['pay_time']) && $orderInfo['pay_time'] == 0)){
                file_put_contents(__DIR__."/".'pay_notify_prepaid.log', var_export($notifyInfo, TRUE));
                $order_status = $orderInfo['order_status'];
                $pay_time = $orderInfo['pay_time'];
                $data['order_status'] = 1;
                $time = time();
                $data['pay_time'] = time();
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态，更新支付时间
                if($rel){
                    // 更新会员余额
                    set_vip_remainder($vipInfo['id'], $orderInfo['finall_benefit'], 1, $order_sn, $notifyInfo['out_trade_no']);
                }else{
                    // 如果更新订单信息失败，就将此错误存储到一个错误表里面
                    $add['order_sn'] = $order_sn;
                    $add['problem_table'] = "prepaid_order";
                    D("prepaid_callback_fail")->add($add);
                }
            }
           # 所有操作成功，返回正常状态，防止微信重复推荐通知
           echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
       }
   }

  
}
