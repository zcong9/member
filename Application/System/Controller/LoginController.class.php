<?php

namespace System\Controller;

class LoginController extends \Think\Controller
{

    public function __construct()
    {
        parent ::__construct();
    }

    public function index()
    {
        if (session("username") && session("UID")) {
            header('Location: ' . U('Index/index'));
            exit;
        } else {
            $this -> display();
        }
    }

    public function captcha()
    {
        $config = array(
            'imageW'   => 200,
            'imageH'   => 40,
            'fontSize' => 20, // 验证码字体大小
            'length'   => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'fontttf'  => '4.ttf',
        );
        $Verify = new \Think\Verify($config);
        ob_clean();
        $Verify->entry();
    }

    public function checkLogin()
    {
        $username = I("post.username/s",'','trim');
        $password = I("post.password/s",'','trim');
        $code = I("post.code/s",'','trim');

        if (!$username || !$password || !$code) exit(json_encode(['code'=>0,'msg' => '请输入 账号、密码 和 验证码']));

        //验证码
        if (!(new \Think\Verify())->check($code)) exit(json_encode(['code'=>0,'msg' => '验证码错误']));

        $info = M('system_admin') -> where(array("account" => $username,'is_del'=>0)) -> find();
        if (empty($info)) exit(json_encode(['code'=>0,'msg' => '账号或密码错误']));

        //密码校验
        $md5Pwd = strtoupper(md5(strrev(md5($password))));
        if ($info['pwd'] !== $md5Pwd) exit(json_encode(['code'=>0,'msg' => '密码错误']));

        //状态校验
        if (1 != $info['status']) exit(json_encode(['code'=>0,'msg' => '账号已经冻结，请联系超级管理员']));

        //更新登录时间
        M('system_admin') -> where(['id'=>$info['id']])->save(['last_ip'=>get_client_ip(),'last_at'=>time(),'login_count'=>$info['login_count']+1]);

        //设置session cookie
        session("roles", $info['roles']);
        session("SYSUID", $info['id']);
        session("account", $info['account']);
        session("real_name", $info['real_name']);
        session("head_pic", $info['head_pic']);

        cookie("account", $info['account'], 86400);
        cookie("real_name", $info['real_name'], 86400);
        cookie("head_pic", $info['head_pic'], 86400);

        exit(json_encode(['code'=>1,'msg'=>'登录成功']));
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        cookie("account", null);
        cookie("real_name", null);
        cookie("head_pic", null);
        header('Location: ' . U('Login/index'));
        exit;
    }
}



