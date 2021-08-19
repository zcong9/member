<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/18
 * Time: 14:56
 */

namespace Admin\Controller;
header("Content-type:text/html;charset=utf-8");
use Think\Controller;
//use Think\Model;
#店铺基础设置管理
class RestaurantSettingController extends Controller
{
    public $merchant_id;
    #检测用户登录情况
    public function __construct()
    {
        Controller::__construct();
        $admin_id = session("re_admin_id");
        if (!$admin_id) {
            redirect("Index/login");
        }
        $merchant_manager_model = D('merchant_manager');
        $merchant            = $merchant_manager_model->where("id = $admin_id")->field("merchant_id,account")->find();

        $this->merchant_id = $merchant['merchant_id'];
        session('merchant_id', $merchant['merchant_id']);
        session('account', $merchant['account']);
    }



    #店铺信息管理
    public function  index(){
        $p = I('get.page',1,'int');
        $keyword = I('get.keyword');
        $storeModel = D("store");
        $condition['merchant_id'] = $this->merchant_id;
        if(!empty($keyword)){
            $condition['store_name'] = array('like',"%".$keyword."%");
        }
        $count = $storeModel->where($condition)->count();
        $Page = new \Think\Page($count,10);
        $show = $Page->show();
        $lists = $storeModel->field($filed)->where($condition)->page($p,10)->order('store_id DESC')->select();
        $this->assign('lists', $lists);
        $this->assign('page',$show);
        $this->display();
    }

    public function show_store(){
        $id = I('get.id');
        $store = D("store")->where(['store_id'=>$id])->find();
        $store['create_at'] = date('Y-m-d H:i:s', $store['create_at']);
        echo json_encode($store);
    }
    public function store_post(){
        $data = I('post.');
        if(empty($data['store_id'])){
            $data['create_at'] = time();
            unset($data['store_id']);
            $data['merchant_id'] = $this->merchant_id;
            $res = D("store")->add($data);
        }else{
            $res = D("store")->save($data);
        }
        
        exit(json_encode(['code'=>1, 'msg'=>'编辑成功']));
    }

    #商家LOGO上传
    public function changeRestaurantLogo()
    {
        $restaurantId    = session("restaurant_id");
        $upload           = new \Think\Upload(); // 实例化上传类
        $upload->maxSize  = 3145728; // 设置附件上传大小
        $upload->exts     = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
        #如果碰上无法上传，出现根目录找不到的情况，可能是文件夹权限导致
        $upload->savePath = '/Public/images/restaurantLogo/';
        $upload->rootPath = './'; // 设置附件上传根目录  必须设置
        $restaurantModel         = D("restaurant");
        $condition['restaurant_id'] = $restaurantId;
        $field = 'logo';
        $rel                      = $restaurantModel->getRestaurant($condition,$field);

        #上传单个文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) {
        # 上传错误提示错误信息
            $msg['code'] = 0;
            $msg['msg']  = "失败";
            exit(json_encode($upload->getError()));
//            $this->error($upload->getError());
        } else {
            // 上传成功 获取上传文件信息
            $data['restaurant_id'] = $restaurantId;
            $data['logo']          = $info['savepath'] . $info['savename'];
            $save_rel   =   $restaurantModel->saveRestaurantInfo($condition,$data);
            if ($rel && $save_rel !== false) {
                if ($rel['logo'] != '/Public/images/logo.png') {
                    unlink("." . $rel['logo']);
                }
                $msg['code'] = 1;
                $msg['msg']  = "成功";
                exit(json_encode($msg));
            } else {
                $msg['code'] = 0;
                $msg['msg']  = "失败";
                exit(json_encode($msg));
            }
        }
    }

    #收款对接
    public function dataForPay()
    {
        $configModel = D("pay_config");
        #查询表中是否开启了普通商户的支付模式，目前需要手动去数据库创建
        $condition['merchant_id'] = $this->merchant_id;
 
        #遍历config表  把2种微信支付和普通支付宝的配置读取出来  serialize
        $configArr = $configModel->where($condition)->select();
        foreach($configArr as $k=>$v){
            $configArr[$k]['config'] = !empty($v['config']) ? unserialize($v['config']): [];
        }

        $this->assign("configList", $configArr);
        $this->display('dataForPay');
    }

    #各方式支付开关
    public function selectPay()
    {
        $pay_select                 = D('pay_select');
        $data                       = $pay_select->create();
        $condition['restaurant_id'] = session("restaurant_id");
        $condition['config_name']   = $data['config_name'];
        $data['business_id']   =   M('restaurant')->where(['restaurant_id'=>session("restaurant_id")])->find()['business_id'];
        $pay_select->savePaySelect($condition,$data);
    }


    #增加或修改支付信息
    public function editAddPayInfo()
    {
        $type        = I("get.type");
        $pay_config  = M('pay_config');
        $id = I('post.id', 0, 'int');
        $pay_data = I('post.');
        $par_arr['merchant_id'] = $this->merchant_id;
        // $configModel->startTrans();
        # 如果该支付方式表中未找到记录，先添加
        if ($type == 'wxpay') {
            $config_arr['appid'] = $pay_data['wxpay_appid'];
            $config_arr['appsecret'] = $pay_data['wxpay_appsecret'];
            $config_arr['mchid'] = $pay_data['wxpay_mchid'];
            $config_arr['key'] = $pay_data['wxpay_key'];
            $par_arr['config'] = serialize($config_arr);
            $par_arr['type'] = 1;
        }
        if(!empty($id)){
            $res = $pay_config->where(['id'=>$id])->save($par_arr);
        }else{
            $res = $pay_config->add($par_arr);
        }
        if($res){
            echo '成功'; 
        }else{
            echo '失败'; 
        }   
    
    }
    # 第四方支付  暂时不支持第四方支付
    public function editAddPayInfos()
    {
        $fourthModel                = D("fourth");
        $data                       = $fourthModel->create();
        $restaurant_id              = session("restaurant_id");
        $data['restaurant_id']      = $restaurant_id;
        $condition['restaurant_id'] = $restaurant_id;
        $tempRel                    = $fourthModel->where($condition)->find();
        if ($tempRel) {
            $key         = C("F_KEY");
            $en          = new Encrypt();
            $data['pwd'] = $en->encrypt($data['pwd'], $key);
            $rel         = $fourthModel->where($condition)->save($data);

            if ($rel !== false) {
                $this->ajaxReturn(1);
            } else {
                $this->ajaxReturn(0);
            }
        } else {
            $key         = C("F_KEY");
            $en          = new Encrypt();
            $data['pwd'] = $en->encrypt($data['pwd'], $key);
            $rel         = $fourthModel->add($data);
            if ($rel) {
                $this->ajaxReturn(1);
            } else {
                $this->ajaxReturn(0);
            }
        }
    }

    # 新版  后台帐号管理
    public function permission(){
        # 查询该店非超级管理员的帐号
        $restaurantManagerModel = D('restaurant_manager');
        $condition['restaurant_id'] = session('restaurant_id');
        $condition['is_main'] = 1;
        $permission = $restaurantManagerModel->selectRestaurantManager($condition);
        $this->assign("permission",$permission);

        # 判断当前登录帐号所属店铺是否有退款权限，有才允许分配退款权限给新帐号
        $res = M('refund_permission')->where(['restaurant_id'=>$condition['restaurant_id']])->find();
        $this->assign("refund_permission",$res);

        # re_admin_id为该登录帐号的主键id
        $where['m.id'] = session('re_admin_id');
        $roleInfo   =   M('restaurant_manager')->alias('m')->join('role r ON r.id = m.role_id')->field('role_id,action_list')->where($where)->find();
        $action_list    =   explode(',',$roleInfo['action_list']);
        $actData = array();
        foreach ($action_list as $k=>$v){
            $map['id']  =   $v;
            $actData[$k]    =   M('action')->field('id,action_name')->where($map)->find();

            if(empty($actData[$k])){
                unset($actData[$k]);
            }else{
                $actData[$k]['role_id'] =   $actData[$k]['id'];
            }

        }
        $this->assign("actData",$actData);

        $this->display();


    }
}