<?php
namespace Admin\Controller;

class IndexController extends BaseController
{
    public function index(){
        if (!session("re_admin_id")) {
            $controller_name = CONTROLLER_NAME;
            $active_name     = ACTION_NAME;
            echo $controller_name;
            echo $active_name;
            if ($controller_name == "Index" && $active_name == "index") {
                return redirect("/index.php/Admin/Index/login");
            }
            return redirect("/index.php/Admin/Index/login");
        }

        $merchant_model = D("merchant");
        $r_where['merchant_id'] = session("merchant_id");
        // $rel = $rmerchant_model->where($r_where)->field("logo,restaurant_name")->find();
        // $logo = $rel['logo'];
        // $this->assign("logo",$logo);

        # 根据当前登录帐号获取角色id，联表获取菜单权限
        $menu   =   $this->CheckRole();
        
        $this->assign('menu', $menu);
        foreach ($menu as $k => $v){
            $url[] = $v['model'].'/'.$v['controller'].'/'.$v['action'];
        }
     
        $this->assign('url', $url);
        $this->display();
    }

    # 权限验证  该位置主要是过滤掉不必要的菜单目录
    public function CheckRole(){
//        $condition['model'] =   MODULE_NAME;
//        $condition['controller'] =   CONTROLLER_NAME;
//        $condition['action'] =   ACTION_NAME;
        $id  =    session("re_admin_id");
        if($id){
            $condition['r.id'] =   $id;
            $actionRes  =   M('merchant_manager')->alias('r')->field('ro.action_list')->join('role ro ON ro.id = role_id')->where($condition)->find();
           
            $actionRes  =   explode(',',$actionRes['action_list']);
            $actionList =   array();
            foreach($actionRes as $v){
                $where['id']            =   $v;
                $action =   M('action')->field('model,controller,action')->where($where)->find();
                if($action){
                    $actionList[]   =   $action;
                }
            }
            return $actionList;
        }
    }

    public function verifyImg()
    {
        $config = array(
            'imageW'   => 160,
            'imageH'   => 41,
            'fontSize' => 20, // 验证码字体大小
            'length'   => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'fontttf'  => '4.ttf',
        );
        $Verify = new \Think\Verify($config);
        ob_clean();
        $Verify->entry();
    }

    public function login()
    {
        $re_admin_id = cookie("re_admin_id");
        if ($re_admin_id) {
            $this->assign("login_account", cookie("login_account"));
            $this->assign("password", cookie("password"));
            $this->assign("autoFlag", 1);
        }
        $this->display();
    }

    //登录校验
    public function checklogin()
    {
        $verify  = new \Think\Verify();
        $Vresult = $verify->check(I('code'));
        if ($Vresult) {
            $restaurant_manager         = D('merchant_manager');
            $condition['account'] = I('login_account');
            $result                     = $restaurant_manager->where($condition)->find();

            $where['merchant_id'] = $result['merchant_id']; //店铺id
            $data                   = M('merchant')->where($where)->find(); //查询出店铺资料
            if ($result) {
                if ($result['pwd'] == md5(I('password'))) {
                    if (I('autoFlag') == 1) {
                        cookie("re_admin_id", $result['id'], 7 * 24 * 3600);
                        cookie("login_account", $result['account'], 7 * 24 * 3600);
                        cookie("password", $result['pwd'], 7 * 24 * 3600);
                    }
                    session("login_account", $result['account']);
                    session('re_admin_id', $result['id']);
                    session('merchant_id', $result['merchant_id']);
                    session("merchant_name", $data['merchant_name']);
                    session("logo", $data['logo']);
                    session("login_way", I('login_way'));
                    $msg['code'] = 1;
                } else {
                    $msg['msg'] = "用户名或者密码有误!";
                    $msg['code'] = 2;
                }
            } else {
                $msg['msg'] = "用户名或者密码有误!";
                $msg['code'] = 3;
            }
        } else {
            $msg['msg'] = "验证码有错误!";
            $msg['code'] = 4;
        }
        exit(json_encode($msg));
    }

    //退出登录
    public function loginout()
    {
        session('re_admin_id', null);
        session('login_account', null);
        session('re_admin_id', null);
        cookie('login_account', null);
        cookie('password', null);
        $msg['msg'] = "退出成功";
        if (session("login_way") == 0) {
            $msg['code'] = 0;
        } else {
            $msg['code'] = 1;
        }
        session("login_way", null);
        exit(json_encode($msg));
    }

    //帐号编辑前填充
    public function account_edit()
    {
        $restaurant_manager = D('restaurant_manager');
        $condition['id']    = I('get.id');
        $object             = $restaurant_manager->where($condition)->find();
        $this->ajaxReturn($object);
    }

//帐号编辑
    public function update_account()
    {
        $restaurant_manager    = D('restaurant_manager');
        $data['id']            = I('post.manager_id');
        $data['login_account'] = I('post.manager_account');
        $data['password']      = md5(I('post.manager_password'));
        $r                     = $restaurant_manager->save($data);

        # 添加修改密码日志
        $RestaurantSetting = new RestaurantSettingController();
        $RestaurantSetting->addChangePwdLog(session('login_account'),$data['login_account'],'xxxxx',$data['password']);
        if ($r) {
            session('login_account', I('post.manager_account'));
            $msg['msg']  = "编辑成功";
            $msg['code'] = 1;
            $msg['data'] = I('post.manager_account');
        } else {
            $msg['msg']  = "编辑失败";
            $msg['code'] = 0;
        }
        $this->ajaxReturn($msg);
    }

    public function upload()
    {

        $this->display();
    }

}
