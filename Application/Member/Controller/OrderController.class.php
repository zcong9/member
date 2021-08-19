<?php

namespace Member\Controller;

use Think\Controller;

class OrderController extends Controller
{
    public function __construct()
    {
        parent ::__construct();
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) exit('请使用微信浏览器打开');
        if (empty(session('USER'))) exit('未登录，请重新进入');
        define("UID", session("USER.id"));
        define("MCH_ID", session("USER.mchId"));
    }

    /**
     * 充值提交
     */
    public function create()
    {
        $total_amount = I("post.account");         //订单总价
        $prepa_id = I("post.prepa_id/d");
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));       //当天开启时间
        $end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;     //当天结束时间
        $condition1['create_at'] = array("between", array($start, $end));     //开启时间与结束时间之间
        $condition1['member_id'] = UID;
        $poMod = D("prepaid_order");
        $num = $poMod -> where($condition1) -> count();        //两时间之间的订单数
        $order_sn = "DC" . str_pad(UID, 5, "0", STR_PAD_LEFT) . date("ymdHis", time()) . str_pad($num + 1, 5, "0", STR_PAD_LEFT);//订单号，$num+1表示同一个会员最新一订单
        $add_time = time();
        $benefit = M('all_benefit') -> where(['id' => $prepa_id, 'type' => 1]) -> find();
        if (!$benefit) exit(json_encode(["msg" => '创建订单失败']));
        if ($total_amount != $benefit['account']) exit(json_encode(["msg" => '创建订单失败']));

        $total_amount = bcmul($benefit['account'], 100);
        $data['order_sn'] = $order_sn; //订单号
        $data['create_at'] = $add_time; //下单时间
        $data['total_amount'] = $total_amount;  //订单总价
        $data['member_id'] = UID;  //会员ID
        $data['merchant_id'] = MCH_ID;
        $data['relation_rule_id'] = $benefit['id'];
        $data['account'] = bcmul($benefit['account'], 100);
        $data['benefit'] = bcmul($benefit['benefit'], 100);
        $data['finall_benefit'] = bcadd($data['account'], $data['benefit']);

        try{
            $poMod->startTrans();
            $result = $poMod -> data($data) -> add();//增加一条订单
            if ($result) {
                $poMod->commit();
                $returnData["code"] = 1;
                $returnData["msg"] = "下单成功";
                $returnData['data'] = ['order_sn'=>$order_sn,'total_amount'=>$total_amount];
                exit(json_encode($returnData));
            }

            $poMod->rollback();
            exit(json_encode(['code'=>0,'msg'=>'创建订单失败']));


        } catch (\Exception $e) {
            $poMod->rollback();
            \Think\Log::write('充值订单：'.$e->getMessage());
            exit(json_encode(['code'=>0,'msg'=>'创建订单异常']));
        }

    }
}