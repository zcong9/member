<?php
namespace System\Controller;
use Think\Controller;

/**
 * Class BaseController
 * @package System\Controller
 */
class BaseController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->checkLogin();
        define("SYSUID", session("SYSUID"));
    }

    //验证是否登录
    public function checkLogin()
    {
        if(!session("SYSUID"))
        {
            $str = "<script type=\"text/javascript\">
          			(function(){
			        	parent.window.location=\"".U('Login/index')."\";
			        })();
			    </script>";
            echo $str;
            exit;
        }
    }
}
