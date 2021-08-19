<?php
namespace Mobile\Controller;
use Think\Controller;
use data\service\Restaurant;
class WeixinController extends Controller {
        #　会员同意授权，如果用户同意授权，页面将跳转至 redirect_uri/?code=CODE&state=STATE。
    public function getUserDetail(){
        // 获取到微信公众号设置链接处传递过来的代理ID
        $restaurant_id = I("get.restaurant_id",0,'int');
        // 存到session中
        session("VIP.restaurant_id",$restaurant_id);

        // 查询出数据库中的当前代理的对应的appid
        $public_number_set = D("public_number_set");
        
        $public_info = $public_number_set->where(['restaurant_id'=>$restaurant_id])->find();
        if(!$public_info){
            exit('缺少店铺参数');
        }
        $appid = $public_info['appid'];
        // 1、获取到code
        $HostURL = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/Mobile/member/receiver_weixin?restaurant_id='.$restaurant_id;
        $redirect_uri = urlencode($HostURL);    // 获取到授权后要跳转到的地址
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
        header("location:".$url);
    }
}