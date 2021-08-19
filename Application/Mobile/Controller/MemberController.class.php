<?php
namespace Mobile\Controller;
use Think\Controller;
use Think\Encrypt;
use payment\wechatpay;
class MemberController extends Controller {
    public function __construct(){
        parent::__construct();
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
//            echo '请使用微信浏览器打开';exit;
        }
        // session('USER', null);
    }

    # 接收由微信确认授权后传递过来的数据，然后注册或者去会员中心
    public function receiver_weixin(){
        // 2、获取到网页授权的Access_token
        // 查询出数据库中的当前代理的对应的appid
        $restaurant_id = I("get.restaurant_id");
        if($restaurant_id != session("VIP.restaurant_id")){
            exit('获取信息错误，请重新进入');
        }
        // 查询出数据库中的当前代理的对应的appid
        $public_number_set = D("public_number_set");
        $publicwhere['restaurant_id'] = $restaurant_id;
        $public_info = $public_number_set->where($publicwhere)->find();
        // 店铺没有公众号使用代理商公众号
        if(!$public_info){
            $restaurant = D("restaurant")->field('restaurant_id,business_id')->where($publicwhere)->find();
            $public_info = $public_number_set->where(['business_id'=>$restaurant['business_id']])->find();
        }

        $appid = $public_info['appid'];
        $appsecret = $public_info['appsecret'];
        $code = I("get.code");
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
        $res = http_get($url);
        $res = json_decode($res);
        $access_token = $res->access_token;
        $openid = $res->openid;
        if(empty($openid)){
            exit('获取信息错误，请重新进入');
        }
        session("VIP.openid",$openid);
        // 3、拉取用户的详细信息
        $url2 = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res2 = http_get($url2);
        $res3 = json_decode($res2,true);
        $res3['restaurant_id'] = $restaurant_id;
        $res = $this->callback($res3);
        if($res){
            $result = D('vip')->where(['id'=>$res])->find();
            if(empty($result['phone']) || empty($result['is_phone'])){
                header("location:".U('Member/redirectReg')); exit;
            }
        }
        header("location:".U('Member/index'));
    }
    public function test_weixin(){
        $res3['headimgurl'] = 'http://xincanyin.net/Public/Uploads/Excel/20210610195539.jpg';
        $res3['nickname'] = '测试6656';
        $res3['sex'] = 1;
        $res3['unionid'] = '558585855';
        $res3['openid'] = 'sdasdasdasd456456467';
        $res3['restaurant_id'] = '267';
        $res = $this->callback($res3);
        if($res){
            $result = D('vip')->where(['id'=>$res])->find();
            if(empty($result['phone']) || empty($result['is_phone'])){
                header("location:".U('Member/redirectReg')); exit;
            }
        }
        header("location:".U('Member/index'));
  
    }

    # 接收由微信确认授权后传递过来的数据，然后注册或者去会员中心
    public function callback($vdata){
        // 是否已登录绑定过
        $vipModel = D("Vip");
        $vipPartyModel = D("vip_third_party");
        if($vdata['unionid']){
            $third = $vipPartyModel->where(array('union_id'=>$vdata['unionid'],'openid'=>$vdata['openid'],'restaurant_id'=>$vdata['restaurant_id']))->find();
        }else{
            $third = $vipPartyModel->where(array('openid'=>$vdata['openid'],'restaurant_id'=>$vdata['restaurant_id']))->find();
        }
        // 启动事务
        $vipModel->startTrans();
        if($third){
            $result = $vipModel->where(['id'=>$third['vip_id']])->find();
            // 存在对应的会员信息，补全openid、头像
            $save['headimgurl'] = $vdata['headimgurl'];
            $save['username'] = $vdata['nickname'];
            $save['sex'] = $vdata['sex'];
            $res = $vipModel->where(['id'=>$result['id']])->save($save);
            $thirdData = [
                'last_login_time'=>time(),
                'login_times'   =>$third['login_times'] + 1,
                'nickname'      => $vdata['nickname'],
            ];
            $resb = $vipPartyModel->where(['id'=>$third['id']])->save($thirdData);
      
            if($res !== false && $resb !== false){
                session("USER",array(
                    'id'=>$result['id'],
                    'openid'=> $vdata['openid'],
                    'restaurant_id' => $vdata['restaurant_id']
                ));
                $vipModel->commit();
                return $result['id'];
            }else{
                return false;
                $vipModel->rollback();
            }

        }else{
            //没有会员添加会员信息，再跳转到手机绑定页
            $arr['restaurant_or_business'] = 2;
            $arr['restaurant_id']   = $vdata['restaurant_id'];
            $arr['business_id']     = 0;
            $arr['username']        = $vdata['nickname'];
            $arr['headimgurl']      = $vdata['headimgurl'];
            $arr['sex']             = $vdata['sex'];
            $arr['add_time']        = time();
            $arr['birthday']        = date("Y/m/d");
            $arr['card_num']        = '';
            $arr['password']        = '';
            $arr['unionid']         = '';
            $arr['xcx_openid']        = '';
            $arr['vip_card']        = '';
            $resa = $vipModel->add($arr);
            $vipid = $vipModel->getLastInsID();
            $thirdData = [
                'vip_id'        => $vipid,
                'restaurant_id' => $vdata['restaurant_id'],
                'union_id'      => $vdata['unionid'],
                'openid'        => $vdata['openid'],
                'create_time'   => time(),
                'last_login_time'=>time(),
                'login_times'   =>1,
                'nickname'      => $vdata['nickname'],
            ];
            $resb = $vipPartyModel->add($thirdData);
            if($resa && $resb){
                session("USER",array(
                    'id'=>$vipid,
                    'openid'=> $vdata['openid'],
                    'restaurant_id' => $vdata['restaurant_id']
                ));
                // 提交事务
                $vipModel->commit();
                return $vipid;
            }else{
                // 回滚事务
                $vipModel->rollback();
                return false;
            }
        }
    }
    //发送短信验证码
    public function sms(){
        $mobile = I("post.mobile");
        $old_or_new = I("post.old_or_new");
        if($mobile){
            // 设置随机数
            $rand = mt_rand(1000,9999);
            // 设置有效时间
            $m = 3;
            // 把加密的内容加密后存入cookie，并设置有效期
            cookie("sms",md5(C("SECURESTR").$rand),$m*60);
            // 判断是读代理还是读店铺
            if(session('restaurant_flag')){
                // 店铺
                // 读取短信配置信息
                $sms_info = M("sms_vip_restaurant")->where(array("restaurant_id"=>session("USER.restaurant_id")))->find();
            }else{
                // 代理
                // 读取短信配置信息
                $sms_info = M("sms_vip")->where(array("business_id"=>session("business_id")))->find();
            }
            $msgid = $sms_info['temp_id'];
            $appkey = $sms_info['appkey'];
            $secret = $sms_info['secret'];
            $sign = $sms_info['sign'];
            $template = "{\"msgcode\":\"$rand\"}";
            // 判断是新用户还是老用户，然后调用不同的短信接口
            if($old_or_new == "0"){
                // 0老用户
                $result = alimsg($appkey,$secret,$mobile,$sign,$template,$msgid);
            }else{
                // 1新用户
                $result = sendSms_new($appkey,$secret,$mobile,$sign,$template,$msgid);
            }

            if($result['code'])
            {
                echo 1;
            }else
            {
                echo "发送失败，原因为：$result[msg]";
            }
        }
    }

    #会员中心页面  注册成功后展示
    public function index(){
        if(session("USER.id")){
            $this->assign('is_user', 1);
            // 查询是否绑定电话
            $result = D("vip")->where(['id'=>session("USER.id")])->find();
            if(empty($result['phone']) || empty($result['is_phone'])){
                header("location:".U('Member/redirectReg')); exit;
            }
        }
        
        $advtVipModel = D("advertisement_vip");
        $where = [];
        $where['restaurant_id'] = session('USER.restaurant_id');

        $where['advertisement_type'] = 0;
        $info = $advtVipModel->where($where)->select();
        $this->assign('info', $info);//顶部
        $where1 = [];
        $where1['restaurant_id'] = session('USER.restaurant_id');
        $where1['advertisement_type'] = 1;
        $info1 = $advtVipModel->where($where1)->select();
        $this->assign('info1', $info1);///底部
        $this->assign('uid',session("USER.id"));
        $this->display();
    }

    #会员个人信息编辑
    public function member_info()
    {
        if(!session("USER.id")){
            header("location:".U('index'));exit;
        }
        $vip = M("vip");
        if(IS_POST){
            // 判断提交过来的生日跟之前数据库的生日是否一样，不一样则进行更新年龄
            $now_birthday = empty(I("post.birthday")) ? date('Y/m/d') : I("post.birthday");
            $confirmPassword = I('post.confirmPassword');
            $password = I('post.password');
            if($password != $confirmPassword) $this->error("两次密码不一致");
            if(!empty($password)){
                // 修改了密码
                $_POST['password'] = md5($password);
            }else{
                unset($_POST['password']);
            }

            $year = explode("/",$now_birthday)[0];
            // 当前年份减去出生年份
            $_POST['age'] =  date("Y")-$year;

            if($vip->create(I("post."))){
                if($vip->save() !== false){
                    // 编辑成功
                    $this->success("保存成功");
                }else{
                    // 编辑失败
                    $this->error("保存失败");
                }
            }else{
                $this->error("保存失败");
            }
        }
        // 根据session里面的电话号码来获取用户信息
        $id = session("USER.id");
        $info = $vip->where(array("id"=>$id))->find();
        $this->assign("info",$info);
        $this->display();
    }
    
    // 授权登录获取openid,判断openid为新用户时跳转到注册
    public function redirectReg(){
        if(!session("USER.id")){
            header("location:".U('index'));exit;
        }
        $this->display("reg");
    }
    // 注册验证
    public function mobileReg()
    {
        if(IS_POST){
            $phoen = I('post.phone');
            if(empty($phoen)){
                $this->error('请填写手机号码');
            }
            
           // 短信验证
           if(cookie("sms") != md5(C("SECURESTR").I("post.smsCode"))){
               $this->error("短信验证错误");
           }
            // 删除cookie
           cookie("sms",null);
            $Vip =  D("Vip");
            $where['aaa'] = $phoen;
            $where['phone'] = $phoen;
            $where['restaurant_id'] = session("USER.restaurant_id");
            $where['id'] = ['neq', session('USER.id')];

            $user = M("vip")->where($where)->find();
            if($user){
                $this->error('电话号码已存在');
            }
   
            // 存在对应的会员信息，补全openid、头像
            $save['phone'] = $phoen;
            $save['is_phone'] = 1;
            $res = $Vip->where(['id'=>session('USER.id'), 'restaurant_id'=>session("USER.restaurant_id")])->save($save);
            if(!$res){
                session("USER", NULL);
                $this->error('绑定手机失败',U("member/index"));
            }else{
                $this->success('登录成功',U("member/index"));
            }
        }

    }
    # 生成会员个人二维码
    public function vip_code(){
        if(!session("USER.id")){
            header("location:".U('index'));exit;
        }
        Vendor('phpqrcode.phpqrcode');

        // 传递会员id过去
        $val = session("USER.id");

        // 加个当前的时间戳，然后到那边后截取出来，再判断那时的时间戳与提交过去的时间戳的时间差是否在一个特定的范围内，是则合法
        $date = time();
        $val = $date."|".$val;

        $key = C("SECRET_KEY");
        $en = new Encrypt();
        $val = $en->encrypt($val,$key);

        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(4);//生成图片大小

        //生成二维码图片
        $object = new \QRcode();
        $object->png('FP'.$val,false, $errorCorrectionLevel, $matrixPointSize,0);
    }

    // 充值
    public function remainder(){
        if(!session("USER.id")){
            header("location:".U('index'));exit;
        }
        $id = session("USER.id");
        $vip = D("vip");
        $vipinfo = $vip->where(array("id"=>$id))->field("remainder,restaurant_id")->find();
        $this->assign("remainder",$vipinfo['remainder']);
        // 查询出预充值的额度
        $where['type'] = 0;
        $where['restaurant_id'] = $vipinfo['restaurant_id'];

        $condition['type'] = 1;
        $condition['restaurant_id'] = $vipinfo['restaurant_id'];
        $prepaid_set = D("business_set")->where($where)->getField("if_open");
        if($prepaid_set){
            $prepaid = D("all_benefit")->where($condition)->order("account asc")->field("id,account,benefit")->select();
            $this->assign('is_p', 2);
            $this->assign("prepaid",$prepaid);
        }else{
            $condition['restaurant_id'] = 0;
            $prepaid = D("all_benefit")->where($condition)->order("account asc")->field("id,account,benefit")->select();
            $this->assign('is_p', 1);
            $this->assign("prepaid",$prepaid);
        }
        $this->display();
    }
//

//
   //  public function ToXml($returnMsg)
   // {
   //     $xml = "<xml>";
   //     foreach ($returnMsg as $key=>$val)
   //     {
   //         if (is_numeric($val)){
   //             $xml.="<".$key.">".$val."</".$key.">";
   //         }else{
   //             $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
   //         }
   //     }
   //     $xml.="</xml>";
   //     return $xml;
   // }
//
//   # 预充值规则处理
//    public function _prepaid($openid,$translate_prepaid,$order_sn){
//       // 根据代理id读取数据库中的预充值数据
//       $business_id = session("business_id");   // 上面prepaid方法存进了session
//       // 数据表all_benefit是预充值和积分一起共用的,区分的字段是type：1代表预充值、2代表积分设置、3代表积分现金、4积分物品
//       $all_benefit = D("all_benefit");
////       $where['business_id'] =  $business_id;
//        // 判断是代理还是店铺
//        if(session('restaurant_flag')){
//            $where['restaurant_id'] =  session('restaurant_id');
//        }else{
//            $where['business_id'] =  session('business_id');
//        }
//       $where['type'] = 1;  // 手动添加类型
//       $pre_rules = $all_benefit->where($where)->order("account asc")->select();
//
//       // 传递过来的预充值额
//       $prepaid = $translate_prepaid;
//       // 最后的充值额
//       $last_prepaid = $prepaid;
//       // 开启了，循环遍历出数据库的预充值规则
//        if(empty($pre_rules)){
//            // 连第一条规则（最小金额的规则）都不满足  那就正常处理
//            $vip = D("vip");
//            $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");
//            // 也就是直接将充值额累加到数据库余额中
//            $prepaid = $translate_prepaid;
//            $total = array('remainder' => $prepaid + $remainder);
//            $res = $vip->where(array("openid"=>$openid))->save($total);
//
//            // 在prepaid_order表更新各种优惠
//            $return = $this->update_benefit_in_order($order_sn,$remainder,0,0,0,$prepaid);
//
//            if($res === false){
//                // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
//                // prepaid_callback_fail    order_sn、problem_table
//                $prepaid_callback_fail = D("prepaid_callback_fail");
//                $add2['order_sn'] = $order_sn;
//                $add2['problem_table'] = "vip";
//                $prepaid_callback_fail->add($add2);
//            }
//            exit;
//        }
//       foreach($pre_rules as $key=>$val){
//           // 判断是不是最后一个规则，并且那个规则的金额小于等于传递过来的价格
//           if($key == count($pre_rules)-1 && $val['account'] <= $prepaid){
//               // 规则的金额小于等于传递过来的充值额  最后的充值额就等于传递过来的加上送的
//               $last_prepaid = $prepaid + $val['benefit'];
//               // 查询出该会员当前在数据库有多少余额
//               $vip = D("vip");
//               $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");
//               $all_money = $last_prepaid + $remainder;
//               // 更新操作
//               $total['remainder'] = $all_money;      // 最后的充值额+余额
//               $res = $vip->where(array("openid"=>$openid))->save($total);
//
//               // 在prepaid_order表更新各种优惠
//               $return = $this->update_benefit_in_order($order_sn,$remainder,$val['id'],$val['account'],$val['benefit'],$last_prepaid);
//
//               if($res === false){
//                   // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
//                   // prepaid_callback_fail    order_sn、problem_table
//                   $prepaid_callback_fail = D("prepaid_callback_fail");
//                   $add2['order_sn'] = $order_sn;
//                   $add2['problem_table'] = "vip";
//                   $prepaid_callback_fail->add($add2);
//               }
//               break;
//           }
//
//           // if判断，如果满足则执行相应的规则（基于一个前提：不是最后一条规则的情况下）
//           if($val['account'] <= $prepaid){    // 如果当前规则的金额小于等于传递过来的预充值额
//               $temp = $pre_rules[$key+1]['account'];      // 当前规则的下一条规则
//               if($temp>$prepaid){                         // 当前规则<当前预充值额<当前规则的下一条规则
//                   $last_prepaid = $prepaid + $val['benefit'];       // 就取当前规则的优惠
//                   // 查询出该会员当前在数据库有多少余额
//                   $vip = D("vip");
//                   $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");
//                   $all_money = $last_prepaid + $remainder;
//                   $total['remainder'] = $all_money;
//
//                   $res = $vip->where(array("openid"=>$openid))->save($total);
//
//                   // 在prepaid_order表更新各种优惠
//                   $return = $this->update_benefit_in_order($order_sn,$remainder,$val['id'],$val['account'],$val['benefit'],$last_prepaid);
//
//                   if($res === false){
//                       // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
//                       // prepaid_callback_fail    order_sn、problem_table
//                       $prepaid_callback_fail = D("prepaid_callback_fail");
//                       $add2['order_sn'] = $order_sn;
//                       $add2['problem_table'] = "vip";
//                       $prepaid_callback_fail->add($add2);
//                   }
//                    break;
//               }
//           }elseif($val['account'] > $prepaid){
//               // 连第一条规则（最小金额的规则）都不满足  那就正常处理
//               $vip = D("vip");
//               $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");
//               // 也就是直接将充值额累加到数据库余额中
//               $prepaid = $translate_prepaid;
//               $total = array('remainder' => $prepaid + $remainder);
//               $res = $vip->where(array("openid"=>$openid))->save($total);
//
//               // 在prepaid_order表更新各种优惠
//               $return = $this->update_benefit_in_order($order_sn,$remainder,0,0,0,$prepaid);
//
//               if($res === false){
//                   // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
//                   // prepaid_callback_fail    order_sn、problem_table
//                   $prepaid_callback_fail = D("prepaid_callback_fail");
//                   $add2['order_sn'] = $order_sn;
//                   $add2['problem_table'] = "vip";
//                   $prepaid_callback_fail->add($add2);
//               }
//               break;
//           }
//       }
//   }

    /**
     * 更新获得优惠后的对应的优惠详情
     * @param $order_sn
     * @param $relation_id
     * @param $account
     * @param $benefit
     * @param $finall_benefit
     * @return bool
     */
//    public function update_benefit_in_order($order_sn,$origin_remainder,$relation_id,$account,$benefit,$finall_benefit){
//        $where['order_sn'] = $order_sn;
//        $data['origin_remainder'] = $origin_remainder;
//        $data['relation_rule_id'] = $relation_id;
//        $data['account'] = $account;
//        $data['benefit'] = $benefit;
//        $data['finall_benefit'] = $finall_benefit;
//
//        $vip_id = D('prepaid_order')->where(array('order_sn'=>$order_sn))->getField('vip_id');
//        $finall_remainder = D('vip')->where(array('id'=>$vip_id))->getField('remainder');
//        $data['finall_remainder'] = $finall_remainder;  // 客户最后的余额
//        $rel = D('prepaid_order')->where($where)->save($data);
//        if($rel !== false){
//            return true;
//        }else{
//            return false;
//        }
//    }
//
//    # 余额明细
    public function touchBalance()
    {
        if(!session("USER.id")){
            header("location:".U('index'));exit;
        }
        $vip_id = session("USER.id");
//        set_vip_remainder($vip_id, 10, 2, 'FP0c63fc1c383f1623057489654'); 测试用的
        $lists = D('account_log')->where(['member_id'=>$vip_id])->order('create_at DESC')->page(1, 10)->select();
        $this->assign('lists', $lists);
        $this->display();
    }


    /**
     * 充值提交
     */
    public function pre_pay_wx(){
        // 生成订单信息再去支付
        $vip_id = session("USER.id");
        $vipModel = D("Vip");
        // 然后根据openID去会员表查询是否有该用户的记录，有则跳转到会员中心，没有则跳转到注册
        $vip_info = $vipModel->field('id, business_id, restaurant_id')->where(array("id"=>$vip_id))->find();
        if(!$vip_info) exit(json_encode(["msg"=>'预充值失败1']));
        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
        $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
        $condition1['vip_id'] = $vip_id;     //会员id

        $prepaid_order = D("prepaid_order");
        $num = $prepaid_order->where($condition1)->count();        //两时间之间的订单数
        $order_sn = "DC".str_pad($vip_id,5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示同一个会员最新一订单
        $add_time = time();            //下单时间
        $total_amount = I("post.account");         //订单总价
        $prepa_id = I("post.prepa_id");
        $benefit = M('all_benefit')->where(['id'=>$prepa_id, 'type'=>1])->find();
        if(!$benefit) exit(json_encode(["msg"=>'预充值失败2']));
        if($total_amount != $benefit['account']) exit(json_encode(["msg"=>'预充值失败3']));
        $condition2['order_sn'] = $order_sn; //订单号
        $condition2['add_time'] = $add_time; //下单时间
        $condition2['total_amount'] = bcmul($benefit['account'], 100);  //订单总价
        $condition2['member_id'] = $vip_id;  //会员ID
        // $condition2['business_id'] = $vip_info['business_id'];
        $condition2['merchant_id'] = $vip_info['restaurant_id'];
        $condition2['relation_rule_id'] = $benefit['id'];
        $condition2['account'] = bcmul($benefit['account'], 100);
        $condition2['benefit'] = bcmul($benefit['benefit'], 100);
        $finall_benefit = bcadd($condition2['account'], $condition2['benefit']);
        $condition2['finall_benefit'] = $finall_benefit;
        $result = $prepaid_order->data($condition2)->add();//增加一条订单

        if($result){
            $r_data["order_sn"] = $order_sn;
            $r_data["total_amount"] = $total_amount;
            $returnData["code"] = 1;
            $returnData["msg"] = "下单成功";
            $returnData['data'] = $r_data;
            exit(json_encode($returnData));

        }else{
            $returnData["msg"] = "预充值失败";
            exit(json_encode($returnData));
        }
    }

     public function test(){
        header('Content-type:text/html; Charset=utf-8');
        // $wechat_pay = cmf_get_option('wechat_pay');

        // 微信JSAPI支付
        $mchid = '1463034402';          //微信支付商户号 PartnerID 通过微信支付商户资料审核后邮件发送
        $appid = 'wxa9be3598671d1982';  //微信支付申请对应的公众号的APPID
        $appKey = '9285e9cb0c11434d8a510ddf7849e2f3';   //微信支付申请对应的公众号的APP Key
        $apiKey = 'founya1234567890founya1234567890';   //https://pay.weixin.qq.com 帐户设置-安全设置-API安全-API密钥-设置API密钥
        
        //①、获取用户openid
        $HostURL = 'http://'.$_SERVER['HTTP_HOST'].'/Mobile/Member/test';
        $HostURL = str_replace('.html', '', $HostURL);
        $WxUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($HostURL).'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
        // 微信授权
        $code = $_GET['code']; // 换取access_token的票据
        if(empty($code)){
            header('location:'.$WxUrl);
            die();
        }
        //获取token openid
        $getToken = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appKey."&code=".$code."&grant_type=authorization_code");
        $jsonToken = json_decode($getToken, true);
        $access_token = $jsonToken['access_token']; // 网页授权接口调用凭证
        $openId = $jsonToken['openid']; // 会员唯一标识
        if(!$openId){
            $this->error('获取openid失败', cmf_url('order/index/index'));
        }

        $wxPay = new \payment\wechatpay($mchid,$appid,$appKey,$apiKey);
        //②、统一下单
        $outTradeNo = time();     //你自己的商品订单号
        $payAmount = 0.01;          //付款金额，单位:元
        $orderName = '三三上门';    //订单标题

        $notifyUrl = 'http://www.soqun360.com';     //付款成功后的回调地址(不要有问号)
        $payTime = time();      //付款时间
        $jsApiParameters = $wxPay->createJsBizPackage($openId,$payAmount,$outTradeNo,$orderName,$notifyUrl,$payTime);
        // dump($jsApiParameters);
        $jsApiParameters = json_encode($jsApiParameters);

        $this->assign('jsApiParameters', $jsApiParameters);
        return $this->fetch('pay');
    }

}