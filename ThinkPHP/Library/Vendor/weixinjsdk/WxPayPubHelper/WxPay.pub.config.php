<?php
/**
 *     配置账号信息
 */

class WxPayConf_pub
{
    //=======【基本信息设置】=====================================
    //微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
    static $APPID = 'wxa9be3598671d1982'; //服务商的
    //受理商ID，身份标识
    static $MCHID = '1411949302'; //服务商的
    //商户支付密钥Key。审核通过后，在微信发送的邮件中查看
    static $KEY = 'yunniu88812345678909876543212345'; //服务商的

    //APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
    static $APPSECRET = '9285e9cb0c11434d8a510ddf7849e2f3';

    static $SUB_APPID = ''; //子商户的
    //JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
    static $SUB_APPSECRET = ''; //子商户的

    static $SUB_MCHID = ''; //子商户的

    //=======【JSAPI路径设置】===================================
    //获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
    const JS_API_CALL_URL = 'http://shop.founpad.com/index.php/mobile/WxPay/pay';
//    const JS_API_CALL_URL = "http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/WxPay/pay";

    //=======【异步通知url设置】===================================
    //异步通知url，商户根据实际开发过程设定
    const NOTIFY_URL = 'http://shop.founpad.com/index.php/home/WxChat/notify';
    // const NOTIFY_URL = "http://".$_SERVER["HTTP_HOST"]."/index.php/home/WxChat/notify";

    //=======【证书路径设置】=====================================
    //证书路径,注意应该填写绝对路径
    //    const SSLCERT_PATH =__DIR__."/".'cacert/apiclient_cert.pem';
    //    const SSLKEY_PATH = __DIR__."/".'cacert/apiclient_key.pem';
    const SSLCERT_PATH = '../cacert/apiclient_cert.pem';
    const SSLKEY_PATH  = '../cacert/apiclient_key.pem';

    //=======【curl超时设置】===================================
    //本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
    const CURL_TIMEOUT = 30;
}



//判断是服务商还是普通商户
$ordinary = D("pay_mode")->where(['merchant_id'=>session("USER.mchId")])->getField("mode");
if ($ordinary == "2") {
    // 普通商户
    $configStr = D('pay_config')->where(['merchant_id' => 1, 'store_id' => 0, 'type' => 1])->getField('config');
    $configArr = unserialize($configStr);
    isset($configArr['appid']) && (WxPayConf_pub::$APPID = $configArr['appid']);//绑定支付的APPID
    isset($configArr['appsecret']) && (WxPayConf_pub::$APPSECRET = $configArr['appsecret']);//公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
    isset($configArr['mchid']) && (WxPayConf_pub::$MCHID = $configArr['mchid']);// 商户号（必须配置，开户邮件中可查看）
    isset($configArr['key']) && (WxPayConf_pub::$KEY = $configArr['key']);//商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）

} else {
    //服务商
    $configStr = D('pay_config')->where(['merchant_id' => session("USER.mchId"), 'store_id' => 0, 'type' => 2])->getField('config');
    $configArr = unserialize($configStr);
    isset($configArr['sub_appid']) && (WxPayConf_pub::$SUB_APPID = $configArr['sub_appid']);
    isset($configArr['sub_appsecret']) && (WxPayConf_pub::$SUB_APPSECRET = $configArr['sub_appsecret']);
    isset($configArr['sub_mchid']) && (WxPayConf_pub::$SUB_MCHID = $configArr['sub_mchid']);
    isset($configArr['key']) && (WxPayConf_pub::$KEY = $configArr['key']);//商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
    isset($configArr['appid']) && (WxPayConf_pub::$APPID = $configArr['appid']);//绑定支付的APPID
    isset($configArr['appsecret']) && (WxPayConf_pub::$APPSECRET = $configArr['appsecret']);//公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
    isset($configArr['mchid']) && (WxPayConf_pub::$MCHID = $configArr['mchid']);// 商户号（必须配置，开户邮件中可查看）

}
