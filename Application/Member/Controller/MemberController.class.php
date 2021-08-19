<?php

namespace Member\Controller;

use Think\Controller;
use Think\Encrypt;
use Admin\Model\BusinessSetModel;
class MemberController extends Controller
{

    public function __construct()
    {
        parent ::__construct();
        // if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) exit('请使用微信浏览器打开');
        // if (empty(session('USER'))) exit('未登录，请重新进入');
        session('USER.id', 5);
        session("USER.openid", 'oToCav7R73DkX4xA2Bz2b3DYw5to');
        session("USER.mchId", '1');
        define("UID", session("USER.id"));
        define("MCH_ID", session("USER.mchId"));
    }

    #会员中心页面  注册成功后展示
    public function index()
    {
        if (UID) {
            $this -> assign('is_user', 1);
            // 查询是否绑定电话
            $result = M('members') -> where(['id' => UID]) -> find();
            if (empty($result['mobile'])) {
                header("location:" . U('Member/redirectReg'));
                exit;
            }
        }

        //广告获取
        $advtVipModel = D("advertisement_vip");
        $where = [];
        $where['merchant_id'] = MCH_ID;
        $where['advertisement_type'] = 0;
        $where['status'] = 1;
        $adtop = $advtVipModel -> field('advertisement_image_url') -> where($where) -> select();
        $this -> assign('adtop', $adtop);
        $where['advertisement_type'] = 1;
        $adbum = $advtVipModel -> field('advertisement_image_url') -> where($where) -> select();
        $this -> assign('adbum', $adbum);

        $this -> assign('uid', UID);
        $this -> display();
    }

    //手机号码绑定
    public function bindMobile()
    {
        $realname = I('post.realname');
        $moblie = I('post.phone/s', '', 'trim');
        $code = I('post.smsCode');
        //判断是否号码与其他账号判断
        if (!$realname) $this -> error('请填写姓名');
        if (!$moblie) $this -> error('请填写手机号码');
        if (!$code) $this -> error('请填写短信验证码');

        if (cookie("sms") != md5(C("SECURESTR") . $code)) $this -> error("短信验证错误");
        // 删除cookie
        cookie("sms", null);

        $mod = M("members");
        $info = $mod -> where(['id' => ['neq', UID], 'is_del'=>0, 'mobile' => ['eq' => $moblie]]) -> find();
        if (!empty($info)) $this -> error('手机号码已绑定其他用户');

        //更新
        $res = $mod -> where(['id' => UID]) -> save(['mobile' => $moblie, 'realname'=>$realname]);

        if (false === $res) {
            session("USER", NULL);
            $this -> error('绑定手机失败', U("member/index"));
        }

        $this -> success('登录成功', U("member/index"));

    }

    //获取二维码
    public function getQrCode()
    {
        if (!UID) {
            header("location:" . U('index'));
            exit;
        }
        Vendor('phpqrcode.phpqrcode');
        // 加个当前的时间戳，然后到那边后截取出来，再判断那时的时间戳与提交过去的时间戳的时间差是否在一个特定的范围内，是则合法
        $date = time();
        $val = $date . "|" . UID;
        $key = C("SECRET_KEY");
        $en = new Encrypt();
        $val = $en -> encrypt($val, $key);
        $errorCorrectionLevel = intval(3);//容错级别
        $matrixPointSize = intval(4);//生成图片大小
        //生成二维码图片
        $object = new \QRcode();
        $object -> png('FP' . $val, false, $errorCorrectionLevel, $matrixPointSize, 0);
    }

    //手机号码注册 绑定微信号
    public function bindWeixin()
    {

    }

    //会员注册
    public function register()
    {

    }

    //会员充值-页面
    public function recharge()
    {
        $money = M('members') -> where(array("id" => UID)) -> getField('money');
        $this -> assign("money", bcdiv($money,100,2));
        // 查询出预充值的额度
        $BusinessSet            = new BusinessSetModel();
        $param['merchant_id'] = MCH_ID;
        $param['type']          = 0;      // 类型为0时预充值
        $setInfo = $BusinessSet->getBusinessSetInfo($param);
        $this->assign('if_open', $setInfo['if_open']);
        $condition['type'] = 1;
        $condition['merchant_id'] = MCH_ID;
        $prepaid = D("all_benefit") -> where($condition) -> order("account asc") -> field("id,account,benefit") -> select();
        
        $this -> assign('is_p', 2);
        $this -> assign("prepaid", $prepaid);
        $this -> display();
    }

    //消费明细
    public function bill()
    {
        $lists = D('account_log') ->field('amount,change_type,desc,order_sn,create_at') -> where(['member_id' => UID]) -> order('create_at DESC') -> page(1, 10) -> select();
        $this->assign('lists', $lists);
        $this->display();
    }
    //消费明细 分页
    public function billPage()
    {
        $page = I('post.page', 1, 'int');
        $lists = D('account_log')->field('amount,change_type,desc,order_sn,create_at') -> where(['member_id' => UID]) -> order('create_at DESC') -> page($page, 10) -> select();
        if(!$lists){
            echo 'end';exit;
        }
        $html = '';
        foreach($lists as $k => $vo){
            $amount = $vo['amount']/100;
            $html .= '<div class="touchBalance_header">订单号：'.$vo['order_sn'].'</div><div class="touchBalance_item"><div class="row"><div class="col-xs-6">'.date('Y-m-d H:i:s',$vo['create_at']).'</div><div class="col-xs-5 text-right">'.$amount.'元</div></div></div><div class="touchBalance_item"><div class="row"><div class="col-xs-4">变动说明：</div><div class="col-xs-7 text-right">'.$vo['desc'].'</div></div></div>';
        }
        echo $html;
    }

    //发送短信验证码
    public function sms()
    {
        $mobile = I("post.mobile/s", '', 'trim');
        $old_or_new = I("post.old_or_new");
        if (empty($mobile)){
            echo '请输入手机号码'; exit;
        } 
        // 设置随机数
        $rand = mt_rand(1000, 9999);
        // 设置有效时间 s
        $m = 3 * 60;
        // 把加密的内容加密后存入cookie，并设置有效期
        cookie("sms", md5(C("SECURESTR") . $rand), $m);
        $sms_info = M("sms_vip") -> where(array("id" => 3)) -> find();
        $msgid = $sms_info['temp_id'];
        $appkey = $sms_info['appkey'];
        $secret = $sms_info['secret'];
        $sign = $sms_info['sign'];
        $template = "{\"msgcode\":\"$rand\"}";
        // 判断是新用户还是老用户，然后调用不同的短信接口
        if ($old_or_new == "0") {
            // 0老用户
            $result = alimsg($appkey, $secret, $mobile, $sign, $template, $msgid);
        } else {
            // 1新用户
            $result = sendSms_new($appkey, $secret, $mobile, $sign, $template, $msgid);
        }

        if ($result['code'] == 1) {
            echo 1;exit;
        }
        echo "发送失败，原因为：".$result['msg']; exit;
    }


    #会员个人信息编辑
    public function member_info()
    {
        $mod = M('members');
        $info = $mod -> where(array("id" => UID)) -> find();
        if (!IS_POST) {
            $info['birthday'] = $info['birthday'] ? date('Y/m/d',$info['birthday']): date('Y/m/d');
            $this -> assign("info", $info);
            $this -> display();
            exit;
        }

        // 判断提交过来的生日跟之前数据库的生日是否一样，不一样则进行更新年龄
        $data['realname'] = I('post.realname/s','','trim');
        $data['realname'] = $data['realname'] ?: $info['realname'];
        $data['birthday'] = empty(I("post.birthday")) ? time() : strtotime(I("post.birthday"));
        $confirmPassword = I('post.confirmPassword/s', '', 'trim');
        $password = I('post.password/s', '', 'trim');
        if ($confirmPassword || $password) {
            if ($password !== $confirmPassword) $this -> error("两次密码不一致");
            $data['pay_pwd'] = md5($password);
        }

        if (false !== $mod -> where(['id' => UID]) -> save($data)) $this -> success("保存成功");
        $this -> error("保存失败");
    }

    // 授权登录获取openid,判断openid为新用户时跳转到注册
    public function redirectReg()
    {
        if (!UID) {
            header("location:" . U('index'));
            exit;
        }
        $this -> display("reg");
    }
}