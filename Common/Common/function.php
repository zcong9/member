<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/25
 * Time: 10:06
 */

include dirname($_SERVER['SCRIPT_FILENAME']).'/Common/Common/fp.function.php';

/**
 * 分区叫号屏推送
 * @param $post_data
 * @return mixed
 */
function sendMsgToDistrictDevice($post_data)
{
    $url = "http://shop.founpad.com:8129";
    $result = http_post($url, $post_data);
    return $result;
}

/**
 * 推送到店铺的所有的分区叫号屏，核销屏，以及汇总叫号屏
 * @param $restaurant_id
 * @param $order_sn
 */
function pushAllDistrict($restaurant_id, $order_sn)
{
    $marks = [];
    $config_model = D("config");
    $where['restaurant_id'] = $restaurant_id;
    $where['config_type'] = "functionality";
    $where['config_name'] = "show_num";
    $mark = $config_model->where($where)->find()['config_value'];
    if (!$mark) {
        echo("");
    }
    $marks[] = $mark;

    $restaurant_district_model = D("restaurant_district");
    $rd_where['restaurant_id'] = $restaurant_id;
    $rd_where['yell_equipment_id'] = ["neq", 0];
    $rel = $restaurant_district_model->where($rd_where)->select();
    if ($rel) {
        $yell_cancel_model = D('yell_cancel');
        foreach ($rel as $r_key => $r_val) {
            $marks[] = $r_val['district_mark'];
            $yc_where['yell_equipment_id'] = $r_val['yell_equipment_id'];
            $cancel_mark = $yell_cancel_model->where($yc_where)->field("cancel_mark")->find()['cancel_mark'];
            $marks[] = $cancel_mark;
        }
    }

    foreach ($marks as $key => $val) {
        $content['tips'] = "下单成功推送showNum";
        $content['order_sn'] = $order_sn;
        $contentJson = json_encode($content);
        $post_data = ["type" => "send", "to" => $val, "content" => $contentJson];
        sendMsgToDistrictDevice($post_data);
    }
}

//���ע����
function create_guid($namespace = '')
{
    static $guid = '';

    $uid = uniqid("", true);

    $data = $namespace;
    $data .= $_SERVER['REQUEST_TIME'];
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['LOCAL_ADDR'];
    $data .= $_SERVER['LOCAL_PORT'];
    $data .= $_SERVER['REMOTE_ADDR'];
    $data .= $_SERVER['REMOTE_PORT'];

    $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
    $guid = substr($hash, 0, 4) . substr($hash, 8, 4) . substr($hash, 12, 4) . substr($hash, 16, 4);

    return $guid;
}

/**
 * @param $msg （要发送的数据【数组】）
 * @param $receiver_value（要发送的机器码）
 * @return bool|mixed
 */
function sendInfo($msg, $receiver_value)
{
    $appkeys = '13007151e25f4a067df93df1';
    $masterSecret = '0ffaf85b288e16aa3d4b29ba';
    $sendno = 4;
    $receiver_value = md5($receiver_value);
    $platform = 'android';
    $msg_content = json_encode(["message" => $msg]);
    $obj = new Think\jpush($masterSecret, $appkeys);
    $rel = $obj->send($sendno, 3, $receiver_value, 2, $msg_content, $platform);
    return $rel;
}

/**
 * 发送模板短信
 * @param to 手机号码集合,用英文逗号分开
 * @param datas 内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
 * @param $tempId 模板Id
 */
function sendTemplateSMS($to, $datas, $tempId, $accountSid1, $accountToken1, $appId1)
{
    global $accountSid, $accountToken, $appId, $serverIP, $serverPort, $softVersion;
    //主帐号
    //    $accountSid= '8a216da8582e9f53015842e551070c79';
    $accountSid = $accountSid1;

    //主帐号Token
    //    $accountToken= 'ec3d93b608f943faa9ef5fcc051b253c';
    $accountToken = $accountToken1;

    //应用Id
    //    $appId='8a216da8582e9f53015842e551ca0c7f';
    $appId = $appId1;

    //请求地址，格式如下，不需要写https://
    $serverIP = 'app.cloopen.com';

    //请求端口
    $serverPort = '8883';

    //REST版本号
    $softVersion = '2013-12-26';

    // 初始化REST SDK

    Vendor("SMS.REST");
    $rest = new \REST($serverIP, $serverPort, $softVersion);
    $rest->setAccount($accountSid, $accountToken);
    $rest->setAppId($appId);

    // 发送模板短信
    // echo "Sending TemplateSMS to $to <br/>";
    $result = $rest->sendTemplateSMS($to, $datas, $tempId);

    /*if($result == NULL ) {
    echo "result error!";
    return;
    }
    if($result->statusCode!=0) {
    echo "error code :" . $result->statusCode . "<br>";
    echo "error msg :" . $result->statusMsg . "<br>";
    //TODO 添加错误处理逻辑
    }else{
    echo "Sendind TemplateSMS success!<br/>";
    // 获取返回信息
    $smsmessage = $result->TemplateSMS;
    echo "dateCreated:".$smsmessage->dateCreated."<br/>";
    echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
    //TODO 添加成功处理逻辑
    }*/

    // by:jcm  2017/1/10
    if ($result == null) {
        $error = "result error!";
    } else if ($result->statusCode != 0) {
        $error = "error code : $result->statusCode <br>error msg :$result->statusMsg";
    } else {
        $error = true;
    }

    return $error;
}

# 阿里大于短信（旧版本）
function alimsg($appkey, $secret, $mobile, $sign, $template, $msgid)
{
    Vendor("SMS.alimsg.TopClient");
    Vendor("SMS.alimsg.AlibabaAliqinFcSmsNumSendRequest");
    Vendor("SMS.alimsg.ResultSet");
    Vendor("SMS.alimsg.TopLogger");
    Vendor("SMS.alimsg.RequestCheckUtil");
    $c = new \TopClient;
    $c->appkey = $appkey;
    $c->secretKey = $secret;
    $c->format = 'json';
    $req = new \AlibabaAliqinFcSmsNumSendRequest;
    // 前端录入参数：$appkey，$secret，短信签名，短信模板ID
    $req->setExtend("123456"); // 公共回传函数，可选
    $req->setSmsType("normal"); // 短信类型，默认normal即可
    $req->setSmsFreeSignName($sign); // 短信签名
    $req->setSmsParam($template); // 短信模板变量，也就是验证码
    $req->setRecNum($mobile); // 要发送到的手机号
    $req->setSmsTemplateCode($msgid); // 短信模板ID
    $resp = $c->execute($req);
    // 返回数据做判断
    if ($resp->result->success) {
        // 成功
        $data['code'] = 1;
    } else {
        // 失败
        $data['code'] = 0;
        $data['msg'] = $resp->sub_msg;
    }
    return $data;
}

/**
 * 阿里短信（搬迁后的，新版本）
 * @param $accessKeyId （官网申请）
 * @param $accessKeySecret（官网申请）
 * @param $mobile（手机号）
 * @param $sign（签名）
 * @param $template（短信内容模板）
 * @param $msgid（短信模板ID）
 * @return bool|mixed
 */
function sendSms_new($accessKeyId, $accessKeySecret, $mobile, $sign, $template, $msgid)
{
    Vendor("SMS.alimsg_new.api_sdk.aliyun-php-sdk-core.Config");
    Vendor("SMS.alimsg_new.api_sdk.Dysmsapi.Request.V20170525.SendSmsRequest");
    Vendor("SMS.alimsg_new.api_sdk.Dysmsapi.Request.V20170525.QuerySendDetailsRequest");

    //短信API产品名
    $product = "Dysmsapi";
    //短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    //暂时不支持多Region
    $region = "cn-hangzhou";

    //初始化访问的acsCleint
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
    $acsClient = new DefaultAcsClient($profile);

    $request = new Dysmsapi\Request\V20170525\SendSmsRequest;
    //必填-短信接收号码
    //    $request->setPhoneNumbers("15000000000");
    $request->setPhoneNumbers($mobile);
    //必填-短信签名
    //    $request->setSignName("阿里测试");
    $request->setSignName($sign);
    //必填-短信模板Code
    //    $request->setTemplateCode("SMS_0001");
    $request->setTemplateCode($msgid);
    //选填-假如模板中存在变量需要替换则为必填(JSON格式)
    //    $request->setTemplateParam("{\"code\":\"12345\",\"product\":\"阿里大于\"}");
    $request->setTemplateParam($template);
    //选填-发送短信流水号
    $request->setOutId("1234");
    //发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    $code = $acsResponse->Code;
    $msg = $acsResponse->Message;
    if ($code == "OK") {
        // 成功
        $data['code'] = 1;
    } else {
        // 失败
        $data['code'] = 0;
        $data['msg'] = $msg;
    }
    return $data;
}

function http_get($url)
{
//    $url = "http://www.jb51.net";
    //初始化
    $ch = curl_init();
    //设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    return $output;
}

function http_post($url, $post_data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // post数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // post的变量
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec($ch);
    curl_close($ch);
    //打印获得的数据
    return $output;
}

function sendMsgToDevice($post_data)
{
    $url = "http://shop.founpad.com:2121";
//    $post_data ? :$post_data = array ("type" => "publish","to" => "1231231231","content" => "json字符串");
    $result = http_post($url, $post_data);
    return $result;
}

/**
 * 支付宝微信配置参数处理
 */
function dealConfigKeyForValue($config)
{
    $dealResult = [];
    foreach ($config as $val) {
        $dealResult[$val['config_name']] = $val['config_value'];
    }
    return $dealResult;
}

/**
 * 获取微信操作对象
 * @staticvar array $wechat
 * @param type $type
 * @return WechatReceive
 */

use PayMethod\Wechat\Loader;
use PayMethod\Wechat\WechatReceive;

function &load_wechat($type = '')
{
    static $wechat = [];
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        $configModel = D('config');
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = session("restaurant_id");
        $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
        $wxpay_c = dealConfigKeyForValue($wxpay_config);

        $config = [
            'token' => 'mytoken',
            'appid' => $wxpay_c['wxpay_appid'], //绑定支付的APPID
            'appsecret' => $wxpay_c['wxpay_appsecret'], //公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置）
            'encodingaeskey' => 'eHSmk5yJN2vSsuYscC8aHIiXnrgXZSKA4MRL9csEwTv',
            'mch_id' => $wxpay_c['wxpay_mchid'], //商户号（必须配置，开户邮件中可查看）
            'partnerkey' => $wxpay_c['wxpay_key'], //商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
            'ssl_cer' => '',
            'ssl_key' => '',
        ];

//        $config = array(
        //            'token'          => 'mytoken',
        //            'appid'          => 'wxa9be3598671d1982',                           //绑定支付的APPID
        //            'appsecret'      => '14c17c03b92fbe64f1bd458561a0da08',             //公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置）
        //            'encodingaeskey' => 'eHSmk5yJN2vSsuYscC8aHIiXnrgXZSKA4MRL9csEwTv',
        //            'mch_id'         => '1411949302',                                   //商户号（必须配置，开户邮件中可查看）
        //            'partnerkey'     => '12345678901234567890123456789012',             //商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        //            'ssl_cer'        => '',
        //            'ssl_key'        => '',
        //        );

        $config['cachepath'] = CACHE_PATH . 'Data/';
        $wechat[$index] = &Loader::get($type, $config);
    }
    return $wechat[$index];
}

function getOrderInfoForBill($order_sn)
{
    //获取订单的基本信息
    $orderModel = order();
    $condition['order_sn'] = $order_sn;
    $orderInfo = $orderModel->where($condition)->field("order_id,order_sn,table_num,total_amount,pay_time,restaurant_id,order_type,pay_type,desk_code,pay_num")->find();

    //获取订单的详细信息
    $order_id = $orderInfo['order_id'];
    $t_condition['order_id'] = $order_id;
    $order_food_model = order_F();
    $order_food_list = $order_food_model->where($t_condition)->field("order_food_id,food_num,food_price2,food_name")->select();
    $food_list = [];
    $ofaModel = order_F_A();
    foreach ($order_food_list as $key => $val) {
        $food = new FoodInfo();
        $food->food_name = $val['food_name'];
        $food->food_num = $val['food_num'];
        $food->food_price = $val['food_price2'];
        $ofa_condition['order_food_id'] = $val['order_food_id'];
        $fo_attr = $ofaModel->where($ofa_condition)->field("food_attribute_name")->select();
        $str = "";
        foreach ($fo_attr as $kasd => $vasd) {
            if ($vasd['food_attribute_name']) {
                $str .= $vasd['food_attribute_name'] . " ";
            }
        }
        $food->food_attr = $str;
        $food_list[] = $food;
    }

    $orderInfo['food_list'] = $food_list;

    return $orderInfo;
}

//毫秒级延时函数 0<dblLong<1000
function MIEA_delay($dblLong)
{
    if ($dblLong >= 1000) {
        $dblLong = 999;
    }

    $startTime = floor(microtime() * 1000);
    $endTime = $startTime + $dblLong;
    if ($endTime > 999) {
        $endTime = $endTime - 999;
    }

    while (floor(microtime() * 1000) != $endTime) {
        //echo floor(microtime()*1000)."<br>";
    }
}

/**
 * 输入年份，返回该年份中每个月的开始时间与结束时间
 * @param $year (rs:2016)
 * @return array
 */
function monthForYear($year)
{
    $month = [];
    for ($i = 1; $i <= 12; $i++) {
        $v = $year . "-" . $i;
        $v2 = $year . "-" . ($i + 1);
        $month_start = strtotime($v);
        if ($i == 12) {
            $month_end = strtotime(($year + 1) . "-1") - 1;
        } else {
            $month_end = strtotime($v2) - 1;
        }
        $month[$i - 1]['month_start'] = $month_start;
        $month[$i - 1]['month_end'] = $month_end;
    }
    return $month;
}

/**
 * 输入年份，月份，输出该月份每一天开始时间与结束时间
 * @param $year
 * @param $month
 * @return array
 */
function dayForMonth($year, $month)
{
    $year ?: $year = date("Y");
    $month ?: $month = date("m");
    $days = get_days_by_year($year, $month); //返回当前月的天数
    $day_list = [];
    for ($i = 1; $i <= $days; $i++) {
        $day_start = date($year . "-" . $month) . "-" . $i;
        $day_end = date($year . "-" . $month) . "-" . $i . " 23:59:59";
        $day_list[$i - 1]['day_start'] = strtotime($day_start);
        $day_list[$i - 1]['day_end'] = strtotime($day_end);
    }
    return $day_list; //返回当前月的每天开始时间与结束时间，形如2016-01-01 00:00:00，2016-01-01 23:59:59的时间戳
}

/**
 * 输入年份，月份，输出该月份的天数
 * @param $year
 * @param $month
 * @return int
 */
function get_days_by_year($year, $month)
{
    //首先判断闰年
    if ($year % 400 == 0 || ($year % 4 == 0 && $year % 100 !== 0)) {
        $rday = 29;
    } else {
        $rday = 28;
    }

    if ($month == 2) {
        $days = $rday;
    } else {
        //判断是大月（31），还是小月（30）
        $days = (($month - 1) % 7 % 2) ? 30 : 31;
    }
    return $days;
}

class FoodInfo
{
    public $food_name;
    public $food_num;
    public $food_price;
    public $food_attr;
}

//横屏客户端模板颜色更换
function change_telcolor()
{
    $restaurant = D('Restaurant');
    $condition['restaurant_id'] = session("restaurant_id");
    $result = $restaurant->where($condition)->field('tplcolor_id')->find()['tplcolor_id'];
    return $result;
}

//竖屏客户端模板颜色更换
function change_telcolor1()
{
    $restaurant = D('Restaurant');
    $condition['restaurant_id'] = session("restaurant_id");
    $result = $restaurant->where($condition)->field('tplcolor1_id')->find()['tplcolor1_id'];
    return $result;
}

//移动端模板颜色更换
function change_telcolor2()
{
    $restaurant = D('Restaurant');
    $condition['restaurant_id'] = session("restaurant_id");
    $result = $restaurant->where($condition)->field('tplcolor2_id')->find()['tplcolor2_id'];
    return $result;
}

//PHPExcel导出excel方式
function exportExcel($expTitle, $expCellName, $expTableData)
{
    // $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
    //    $xlsTitle = "营业额报表、导出时间(".date("Y-m-d",time()).")";
    // $fileName = $_SESSION['account'].date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
    // $fileName = "营业额报表、导出时间(" . date("Y-m-d", time()) . ")";
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    vendor("PHPExcel.PHPExcel");

    import("Org.Util.PHPExcel");
    $objPHPExcel = new \PHPExcel();
    $cellName = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];

    // $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); //合并单元格
    // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
    for ($i = 0; $i < $cellNum; $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    for ($i = 0; $i < $dataNum; $i++) {
        for ($j = 0; $j < $cellNum; $j++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 2), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    $fileName = iconv("utf-8", "gb2312", $expTitle);
    $objPHPExcel->setActiveSheetIndex(0);
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=\"$fileName\"");
    header('Cache-Control: max-age=0');
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output'); //文件通过浏览器下载

    // header('pragma:public');
    // header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
    // header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
    // import("Org.Util.PHPExcel.IOFactory");
    // $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    // $objWriter->save('php://output');
    exit;
}

//PHPExcel导出excel方式
function exportExcel1($expTitle, $xlsSearchDate, $expTableData)
{
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
    //    $xlsTitle = "营业额报表、导出时间(".date("Y-m-d",time()).")";
    // $fileName = $_SESSION['account'].date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
    $fileName = $expTitle;
    $dataNum = count($expTableData);
    vendor("PHPExcel.PHPExcel");

    import("Org.Util.PHPExcel");
    $objPHPExcel = new \PHPExcel();
    $cellName = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];

    $objPHPExcel->getActiveSheet(0)->mergeCells('B1:I1'); //合并单元格
    $objPHPExcel->getActiveSheet(0)->setCellValue('B1', $xlsSearchDate);
    for ($i = 2; $i < $dataNum * 4; $i += 4) {
        static $j = 0;
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[0] . $i, $j + 1);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[1] . $i, $expTableData[$j]['food_name']);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[2] . $i, '当前查询');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[3] . $i, $expTableData[$j]['food_num'] . "份");

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[2] . ($i + 1), $expTableData[$j]['year1'] . "年"); //前年
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[2] . ($i + 2), $expTableData[$j]['year'] . "年"); //去年
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[2] . ($i + 3), "月份"); //去年

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[3] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][0] . "份"); //1月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[4] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][1] . "份"); //2月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[5] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][2] . "份"); //3月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[6] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][3] . "份"); //4月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[7] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][4] . "份"); //5月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[8] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][5] . "份"); //6月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[9] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][6] . "份"); //7月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[10] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][7] . "份"); //8月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[11] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][8] . "份"); //9月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[12] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][9] . "份"); //10月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[13] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][10] . "份"); //11月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[14] . ($i + 1), $expTableData[$j]['lastyear_allOrderNum1'][11] . "份"); //12月

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[3] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][0] . "份"); //1月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[4] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][1] . "份"); //2月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[5] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][2] . "份"); //3月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[6] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][3] . "份"); //4月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[7] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][4] . "份"); //5月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[8] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][5] . "份"); //6月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[9] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][6] . "份"); //7月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[10] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][7] . "份"); //8月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[11] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][8] . "份"); //9月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[12] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][9] . "份"); //10月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[13] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][10] . "份"); //11月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[14] . ($i + 2), $expTableData[$j]['lastyear_allOrderNum'][11] . "份"); //12月

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[3] . ($i + 3), "1月"); //1月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[4] . ($i + 3), "2月"); //2月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[5] . ($i + 3), "3月"); //3月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[6] . ($i + 3), "4月"); //4月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[7] . ($i + 3), "5月"); //5月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[8] . ($i + 3), "6月"); //6月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[9] . ($i + 3), "7月"); //7月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[10] . ($i + 3), "8月"); //8月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[11] . ($i + 3), "9月"); //9月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[12] . ($i + 3), "10月"); //10月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[13] . ($i + 3), "11月"); //11月
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[14] . ($i + 3), "12月"); //12月

        $j++;
    }

    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
    import("Org.Util.PHPExcel.IOFactory");
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
}

//推送取餐信息取餐叫号机和核销机
function sendTakeOutMsg($order_sn)
{
    $orderModel = D("order_sn");
    $o_condition['order_sn'] = $order_sn;
    //获取订单信息，判断是否要推送到展示餐牌号展示页面
    $orderInfo = $orderModel->where($o_condition)->field("table_num,desk_code,restaurant_id")->find();
    $restaurantModel = D("Restaurant");
    $rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
    $show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];
    if ($orderInfo['table_num'] == 0 && $orderInfo['desk_code'] == 0) {
        $content['tips'] = "下单成功推送showNum";
        $contentJson = json_encode($content);
        $post_data = ["type" => "publish", "to" => $show_device_code, "content" => $contentJson];
        $rel2 = sendMsgToDevice($post_data);
    }
}

function array_page($array, $rows = 15, $page = 1)
{
    $count = count($array);
    $Page = new \Think\PageAjax($count, $rows);
    $pageattr['show'] = $Page->show(''); // 分页显示输出
    $pageattr['list'] = array_slice($array, ($page - 1) * $rows, $rows);
    return $pageattr;
}

// by:jcm 2017/1/9
# 断点调试
function p($str = '')
{
    echo "<pre>";
    print_r($str);
    echo "</pre>";
    exit;
}

//Auth权限验证 - 控制器
function action_AuthCheck($ruleName, $userId = '', $relation = 'or')
{
    $Auth = new \Think\Auth();
    if (empty($userId)) {
        $userId = $_SESSION['manager_id'];
    }
    $type = 1;
    $mode = 'url';
    return $Auth->check($ruleName, $userId, $type, $mode, $relation);
}

//Auth权限验证 - 模板调用
function tpl_AuthCheck($ruleName, $userId, $relation = 'or', $t, $f = 'false')
{
    //$relation = or|and; //默认为'or' 表示满足任一条规则即通过验证; 'and'则表示需满足所有规则才能通过验证
    $Auth = new \Think\Auth();

    if (empty($userId)) {
        //用户ID判断，没有就取当前登录的用户ID
        $userId = $_SESSION['manager_id'];
    }
    $type = 1; //分类-具体是什么没搞懂，默认为:1
    $mode = 'url'; //执行check的模式,默认为:url
    return $Auth->check($ruleName, $userId, $type, $mode, $relation) ? $t : $f;
}

# 单文件上传
function upload()
{
    // 文件域的下标（字段名）
    $key = key($_FILES);

    // 如果没有上传文件
    if (!$_FILES[$key]['size']) {
        return [];
    }

    $upload = new \Think\Upload(); // 实例化上传类
    $upload->maxSize = 5 * 1024 * 1024; // 设置附件上传大小
    $upload->exts = ['jpg', 'gif', 'png', 'jpeg']; // 设置附件上传类型

    // 方法一：(目录程序会自动创建)
    $upload->rootPath = './';
    /* $upload->savePath  = ltrim($_POST['_rootpath'], '/') . '/'; // 设置附件上传目录*/
    $upload->savePath = ltrim($_POST['_rootpath'] ? $_POST['_rootpath'] : "/Public/Uploads/Goods_desc", '/') . '/'; // 设置附件上传目录

    // 方法二(目录必须手工创建)
    # $upload->rootPath = './Public/Uploads/'; // 设置附件上传目录

    // 上传文件
    $info = $upload->upload();

    // 已上传的图片
    /*$f = $info[$key]['savepath'] . $info[$key]['savename'];

    // 生成缩略图
    $image = new \Think\Image();

    $arr = pathinfo($f);
    $image->open($f);
    // 第三个参数，1表示等比例缩放
    $image->thumb(C("THUMB.smaw"), C("THUMB.smah"), 1)->save("$arr[dirname]/$arr[filename]_sma.jpg");*/

    // 入库
    $_POST[$key] = date('Y-m-d') . '/' . $info[$key]['savename'];

    if (!$info) {
        // 上传错误提示错误信息
        return $upload->getError();
    } else {
        return $info;
    }
}

/**
 * 获取指定规格的缩略图
 * @param string $org 原图路径
 * @param string $spec 规格
 * @return string 规格图路径
 */
function get_thumb($org, $spec = 'sma')
{
    $arr = pathinfo($org);
    return "$arr[dirname]/$arr[filename]_$spec.jpg";
}

//多维数组转一维数组
function arrayChange($a)
{
    static $arr2;
    foreach ($a as $v) {
        if (is_array($v)) {
            arrayChange($v);
        } else {
            $arr2[] = $v;
        }
    }
    return $arr2;
}

//通过年月得当月日期数组（仅含‘日’）
function get_day($year, $month)
{

    if (in_array($month, ['1', '3', '5', '7', '8', '01', '03', '05', '07', '08', '10', '12'])) {
        // $text = $year.'年的'.$month.'月有31天';
        $text = '31';
    } else if ($month == 2) {
        if ($year % 400 == 0 || ($year % 4 == 0 && $year % 100 !== 0)) //判断是否是闰年
        {
            // $text = $year.'年的'.$month.'月有29天';
            $text = '29';
        } else {
            // $text = $year.'年的'.$month.'月有28天';
            $text = '28';
        }
    } else {
        // $text = $year.'年的'.$month.'月有30天';
        $text = '30';
    }

    for ($i = 1; $i <= $text; $i++) {
        $r[] = $i;
    }

    return $r;
}

/**
 * 计算两点地理坐标之间的距离
 * @param Decimal $longitude1 起点经度
 * @param Decimal $latitude1 起点纬度
 * @param Decimal $longitude2 终点经度
 * @param Decimal $latitude2 终点纬度
 * @param Int $unit 单位 1:米 2:公里
 * @param Int $decimal 精度 保留小数位数
 * @return Decimal
 */
function getDistance1($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
{

    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;

    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;

    if ($unit == 2) {
        $distance = $distance / 1000;
    }

    return round($distance, $decimal);
}

/**
 * 美团推送拼接参数的http_get请求
 * @param String $url1
 * @param Array $da1
 * @param String $consumer_secret1
 */
function http_get_param($url1, $da1, $consumer_secret1)
{
    foreach ($da1 as $key => $value) {
        $data_key1[] = $key;
    }
    sort($data_key1); //排序参数key值
    $str1 = ''; //拼接参数
    foreach ($data_key1 as $key => $value) {
        $str1 .= $value . '=' . $da1[$value] . '&';
    }
    $str1 = substr($str1, 0, strlen($str1) - 1);
    $sign_str1 = $url1 . '?' . $str1 . $consumer_secret1; //加密前字符串
    $sig1 = md5($sign_str1); //加密后字符串
    $request_url1 = $url1 . '?' . $str1 . '&sig=' . $sig1; //请求url
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request_url1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    return $output;
}

/**
 * 美团聚宝盆生成数字签名
 * @param Array $arr 要参与计算和拼接的系统级参数（如果是get请求则还要包含业务级参数）
 * @param $signkey
 * @return string
 */
function jubaopen_confirm($arr, $signkey)
{
    foreach ($arr as $key => $value) {
        if ($value) {
            $data_key[] = $key;
        }
    }
    sort($data_key); //排序参数key值
    $str = '';
    foreach ($data_key as $val) {
        $str .= $val . $arr[$val];
    }
    $str = $signkey . $str;
    $sha1 = sha1($str);
    $sha1 = strtolower($sha1);
    return $sha1;
}

/**
 * 聚宝盆http post公共请求方法（包含参数拼接和数字签名生成）
 * @param String $url （请求的第三方接口地址）
 * @param Array $system_param （系统级参数）（签名排序和sha1加密生成只需要系统级参数参与）
 * @param Array $application_param （业务级参数）
 * @return mixed
 */
function jubaopen_http_post($url, $system_param, $application_param)
{
    $signkey = D('meituan_config')->getField('signkey');
    // 获取数字签名
    $receive = jubaopen_confirm($system_param, $signkey);
    // 拼接URL参数（系统级参数参与拼接）  (在之前系统级参数的数组的基础上加上sign)
    $system_param['sign'] = $receive;
    $str1 = ''; //拼接参数
    foreach ($system_param as $key => $value) {
        $str1 .= $key . '=' . $value . '&';
    }
    $str1 = substr($str1, 0, strlen($str1) - 1);
    $end_url = $url . '?' . $str1;
    $res = http_post($end_url, $application_param);
    return $res;
}

/**
 * 聚宝盆http get公共请求方法（包含参数拼接和数字签名生成）
 * @param String $url （请求的第三方接口地址）
 * @param Array $system_param （签名排序和sha1加密生成需要系统级参数和业务级参数一起参与）
 * @return mixed
 */
function jubaopen_http_get($url, $system_param)
{
    $signkey = D('meituan_config')->getField('signkey');
    // 获取数字签名
    $receive = jubaopen_confirm($system_param, $signkey);
    // 拼接URL参数（系统级参数参与拼接）  (在之前系统级参数的数组的基础上加上sign)
    $system_param['sign'] = $receive;

    $str1 = ''; //拼接参数
    foreach ($system_param as $key => $value) {
        $str1 .= $key . '=' . $value . '&';
    }
    $str1 = substr($str1, 0, strlen($str1) - 1);
    $end_url = $url . '?' . $str1;
    $res = http_get($end_url);
    return $res;
}

/**
 * 获取token信息，如果token过期就重新刷新token
 * @param restaurant_id，点餐系统的店铺id
 * @return mixed
 */

use ElemeOpenApi\Config\Config;
use ElemeOpenApi\OAuth\OAuthClient;

function get_or_refresh_token($restaurant_id)
{
    Vendor('ElemeOpenApi.Config.Config');
    Vendor('ElemeOpenApi.OAuth.OAuthClient');

    $where['restaurant_id'] = $restaurant_id;
    // 每家饿了么店铺的token以及其他信息
    $token_info = D('eleme_token')->where($where)->find();
    // 饿了么应用配置信息
    $eleme_config = D('eleme_config')->find();
    $app_key = $eleme_config['app_key'];
    $app_secret = $eleme_config['app_secret'];
    //实例化一个配置类
    //    $config = new Config($app_key, $app_secret, true);
    $config = new Config($app_key, $app_secret, false);

    $expires_in = $token_info['expires_in']; // token有效期
    $create_time = $token_info['create_time']; // token创建时间（或者刷新时间）
    $now = time(); // 当前时间
    // 距离过期时间小于等于多少并且大于0就refresh获取token（单位：秒），如果距离时间小于0则refresh_token失效
    $range_time = $expires_in - ($now - $create_time);
    // 初始化是否需要重新授权为：1不需要重新授权，2为需要重新授权
    $again_grant = 1;
    if ($range_time < C("ELEME_EXPIRES_IN") && $range_time > 0) {
        $refresh_token = $token_info['refresh_token'];
        $scope = "all";
        //使用config对象，实例化一个授权类
        $client = new OAuthClient($config);
        $return = $client->get_token_by_refresh_token($refresh_token, $scope);
        $arr = (array)$return;
        // 刷新后得到的新数据
        $save['access_token'] = $arr['access_token'];
        $save['expires_in'] = $arr['expires_in'];
        $save['refresh_token'] = $arr['refresh_token'];
        $save['create_time'] = time();
        // 更新到数据表
        $res = D('eleme_token')->where($where)->save($save);

        // refresh了token就重新获取数据库中的token数据供后续使用
        $token_info = D('eleme_token')->where($where)->find();
    } else if ($range_time <= 0) {
        // refresh_token也失效，只能重新授权
        $del = D('eleme_token')->where(['restaurant_id' => $restaurant_id])->delete();
        $again_grant = 2; // 2为需要重新授权
        file_put_contents(__DIR__ . "/" . "grant_against.txt", "restaurant_id:" . $restaurant_id . "|range_time:" . $range_time .
            "|expires_in:" . $expires_in . "|now:" . $now . '|create_time:' . $create_time . '|C:' . C("ELEME_EXPIRES_IN") .
            "|时间" . date("Y-m-d H:i:s") . "\r\n\r\n", FILE_APPEND);
    }
    $token_info['again_grant'] = $again_grant; // 是否需要重新授权
    // 返回原有的token或者刷新后的token数据
    return $token_info;
}

/**
 * 删除订单二维码
 * @param $order_sn 订单号
 * @param int $type 类型 1是第四方，2是第三方
 */
function delQrcode($order_sn, $type = 1)
{
    if ($type == 1) {
        @unlink('img/fourth/wx' . $order_sn . '.png');
        @unlink('img/fourth/ali' . $order_sn . '.png');
    } else {
        @unlink('img/third/wx' . $order_sn . '.png');
        @unlink('img/third/ali' . $order_sn . '.png');
    }

}

// 年月order表
function order()
{
    // 带有年月后缀的订单表的表名
    $yearMonth = date('Y') . date('m');
    return M('order_' . $yearMonth);
}

// 上一个月的order表
function lastOrder()
{
    // 带有年月后缀的订单表的表名
    $yearMonth = date('Y') . date('m');
    return M('order_' . $yearMonth);
}

// 年月order_food表
function order_F()
{
    // 带有年月后缀的订单表的表名
    $yearMonth = date('Y') . date('m');
    return M('order_food_' . $yearMonth);
}

// 年月order_food_attribute表
function order_F_A()
{
    // 带有年月后缀的订单表的表名
    $yearMonth = date('Y') . date('m');
    return M('order_food_attribute_' . $yearMonth);
}


// 年月order_food_specification表
function order_F_S()
{
    // 带有年月后缀的订单表的表名
    $yearMonth = date('Y') . date('m');
    return M('order_food_specification_' . $yearMonth);
}

/**
 * 根据时间段计算间隔月份
 * @param $startTime 开始时间戳
 * @param $endTime 结束时间戳
 * @param $type 类型，1order,2order_food,3order_food_attribute
 * @return array
 */
function yearMonthTable($time1, $time2, $type = 1)
{
    $yearMonth = [];
    if ($type == 1) {
        $table = 'order_';
    } else if ($type == 2) {
        $table = 'order_food_';
    } else {
        $table = 'order_food_specification_';
    }

    if (ifExist($table . date('Y', $time1) . date('m', $time1))) {
        $yearMonth[] = $table . date('Y', $time1) . date('m', $time1);
    }
    while (($time1 = strtotime('+1 month', $time1)) <= $time2) {
        if (ifExist($table . date('Y', $time1) . date('m', $time1))) {
            $yearMonth[] = $table . date('Y', $time1) . date('m', $time1);
        }
    }
    //最后一个月的月份
    if (ifExist($table . date('Y', $time2) . date('m', $time2))) {
        $yearMonth[] = $table . date('Y', $time2) . date('m', $time2);
    }
    $yearMonth = array_unique($yearMonth);
    return $yearMonth;
}

// 判断表是否存在
function ifExist($table_name)
{
    $isTable = M()->query("SHOW TABLES LIKE '$table_name'");
    if ($isTable) {
        return true;
    } else {
        return false;
    }
}

/** 联合查询
 * @param int $startTime 结束时间
 * @param int $endTime 开始时间
 * @param int $type order、order_food、order_food_attribute表的区分
 * @param string $sql sql语句
 * @return mixed 返回数据记录
 */
function unionSelect($startTime = 1483203661, $endTime = 1512061261, $type = 1, $sql_orignal = 'SELECT
            *
        FROM
            `tabName`
        WHERE
            `add_time` BETWEEN 1483203661
        AND 1514394061
        AND `pay_type` IN (0, 1, 2, 3, 4, 5)
        AND `order_type` IN (1, 2, 3)
        AND `restaurant_id` = 131
        AND `order_status` <> 0
        GROUP BY
            order_sn
        ORDER BY
    order_id DESC')
{
    $tables = yearMonthTable($startTime, $endTime, $type);
    $str = '';
    foreach ($tables as $key => $val) {
        if (ifExist($val)) {
            $sql = str_replace("tabName", $val, $sql_orignal);
            if ($key == 0) {
                $str .= "($sql)";
            } else {
                $str .= " UNION ALL ($sql)";
            }
        }
    }
    if ($str == '') {
        return [];
    }
    $res = M()->query($str);
    return $res;
}

/**
 * 统计数量
 * @param int $startTime 开始时间
 * @param int $endTime 结束时间
 * @param int $type 类型，1 order表，2 order_food表 3 order_food_attribute
 * @param string $sql_orignal sql语句
 * @param string $field 要统计的子句的那一列
 * @return mixed 返回总数
 */
function countNum($startTime = 1483203661, $endTime = 1514394061, $type = 1, $sql_orignal = "SELECT COUNT(*) AS tp_count FROM tabName WHERE `add_time` BETWEEN 1483203661 AND 1514394061 AND `pay_type` IN (0,1,2,3,4,5) AND `order_type` IN (1,2,3) AND `restaurant_id` = 131 AND `order_status` <> 0 LIMIT 1  ", $field = 'tp_count')
{
    $tables = yearMonthTable($startTime, $endTime, $type);
    $str = '';
    foreach ($tables as $key => $val) {
        if (ifExist($val)) {
            $sql = str_replace("tabName", $val, $sql_orignal);
            if (count($tables) == 1) {
                $str .= "select sum($field) AS total
                         from (
                              ($sql)) t";
            } else {
                if ($key == 0) {
                    $str .= "select sum($field) AS total
                         from (
                              ($sql)";
                } else if ($key == count($tables) - 1) {
                    $str .= " UNION ALL ($sql)) t";
                } else {
                    $str .= " UNION ALL ($sql)";
                }
            }
        }
    }
    if ($str == '') {
        return 0;
    }
    $res = M()->query($str);
    return $res[0]['total'];
}

/** 多表联合查询
 * @param int $startTime 结束时间
 * @param int $endTime 开始时间
 * @param string $sql sql语句
 * @param string $type 区分是否是分页查询，1否，2是
 * @return mixed 返回数据记录
 */
function unionSelect2($startTime = 1483203661, $endTime = 1512061261, $sql_orignal = 'SELECT
            *
        FROM
            `tabName1`
        WHERE
            `add_time` BETWEEN 1483203661
        AND 1514394061
        AND `pay_type` IN (0, 1, 2, 3, 4, 5)
        AND `order_type` IN (1, 2, 3)
        AND `restaurant_id` = 131
        AND `order_status` <> 0
        GROUP BY
            order_sn
        ORDER BY
    order_id DESC', $type = 1, $limit1 = 0, $limit2 = 25)
{

    //判断传进来的sql语句是否含有tabName1,tabName2,tabName3
    if (strpos($sql_orignal, 'tabName1') !== false) {
//含有order表
        $table1 = yearMonthTable($startTime, $endTime, 1);
    }

    if (strpos($sql_orignal, 'tabName2') !== false) {
//含有order_food表
        $table2 = yearMonthTable($startTime, $endTime, 2);
    }

    if (strpos($sql_orignal, 'tabName3') !== false) {
//含有order_food_specification表
        $table3 = yearMonthTable($startTime, $endTime, 3);
    }

    $tmp = empty($table1) ? $table2 : $table1;
    $tables = empty($tmp) ? $table3 : $tmp;
    $str = '';
    foreach ($tables as $key => $val) {
        if (ifExist($val)) {
            $sql = str_replace("tabName1", $table1["$key"], $sql_orignal);
            $sql = str_replace("tabName2", $table2["$key"], $sql);
            $sql = str_replace("tabName3", $table3["$key"], $sql);

            if ($key == 0) {
                $str .= "($sql)";
            } else {
                $str .= " UNION ALL ($sql)";
            }
        }
    }
    if ($str == '') {
        return [];
    }
    // 区分是否分页查询
    if ($type == 2) {
        $str .= " LIMIT $limit1,$limit2";
    }
    $res = M()->query($str);
    return $res;
}

/**
 * 删除开卡费用二维码订单
 * @param $order_sn 订单号
 * @param int $type 类型 1是第三方，2是第四方
 */
function delVipCardQrcode($order_sn, $type = 1)
{
    if ($type == 1) {
        @unlink('img/vipCard/wx' . $order_sn . '.png');
        @unlink('img/vipCard/ali' . $order_sn . '.png');
    } else {

    }
}

/**
 * 删除预充值二维码订单
 * @param $order_sn 订单号
 * @param int $type 类型 1是第三方，2是第四方
 */
function delPrepaidQrcode($order_sn, $type = 1)
{
    if ($type == 1) {
        @unlink('img/prepaid/wx' . $order_sn . '.png');
        @unlink('img/prepaid/ali' . $order_sn . '.png');
    } else {

    }
}

function httpsPost($url, $data, $header = null)
{
    $ch = curl_init();
    //头部设置
    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    // 设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对证书来源的检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($ch, CURLOPT_POST, 1); // 发送一个 常规的Post请求
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回

    $result = curl_exec($ch); // 执行操作
    // $result['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
//            $this->logObj->logDebug('Errno ',array('msg'=>curl_error($ch)));
        echo "Errno" . curl_error($ch); // 捕抓异常
    }
    curl_close($ch); // 关闭CURL
    return $result;
}

//返回json 通用方法
function returnJson($code, $data)
{
    $returnData['code'] = $code;
    $returnData['msg'] = $data;
    echo json_encode($returnData);
    exit;
}

/**
 * 添加/修改菜时分类
 * @param object $timing
 * @param int $category_id
 * @param string $type
 * @return int status
 */
function categoryTiming($timing, $category_id, $type)
{
    if ($timing && isset($type) && $category_id) {
        $Model = M('food_category_timing');
        $Model->startTrans();
        $timing = json_decode($timing);
        try {
            $where['food_category_id'] = $category_id;
            // 清除旧的定时，重新添加
            if ($Model->where($where)->find()) {
                $Model->where($where)->delete();
            }

            // 固定日期分类
            if ($type == 'today') {
                foreach ($timing as $t_key => $t_val) {
                    if ($t_val[0] && $t_val[1]) {
                        $t_condition['day_start_time'] = strtotime($t_val[0]);
                        $t_condition['day_end_time'] = strtotime($t_val[1]);
                        $t_condition['food_category_id'] = $category_id;
                        $Model->add($t_condition);
                    }
                }
            } else {
                // 星期定时分类
                foreach ($timing as $d_key => $d_val) {
                    $length = count($d_val);
                    if ($length > 2) {
                        $d_data['timing_day'] = '';
                        for ($i = 0; $i < $length - 2; $i++) {
                            if ($i == ($length - 3)) {
                                $d_data['timing_day'] .= $d_val[$i];
                            } else {
                                $d_data['timing_day'] .= $d_val[$i] . "-";
                            }
                        }
                        $d_data['start_time'] = $d_val[$length - 2] ?: '00:00';
                        $d_data['end_time'] = $d_val[$length - 1] ?: '00:00';
                        $d_data['food_category_id'] = $category_id;
                        $Model->add($d_data);
                    }
                }
            }
            $Model->commit();
            return 1;
        } catch (\Exception $e) {
            $Model->rollback();
            return 0;
        }
    }
}


/**
 * 判断分类是否超时
 * @param $category_id
 * @return int status
 */
function categoryTimingStatus($category_id)
{
    $categoryTimingList = M('food_category_timing')->where(['food_category_id' => $category_id])->select();

    if (!$categoryTimingList) return 1;

    $nowTime = time();   // 当前时间
    $status = 0;
    foreach ($categoryTimingList as $cTiming) {
        // 星期定时
        if ($cTiming['timing_day'] !== 'today') {
            $timingWeek = explode("-", $cTiming['timing_day']);
            // 当日星期几
            $whichWeek = date('w', time());
            $timingStartTime = strtotime($cTiming['start_time']);    // 开始时间
            $timingEndTime = strtotime($cTiming['end_time']);      // 结束时间
            // 当日处于星期定时范畴内且当前时间大于开始时间小于结束时间则上架分类，反之表示不在该分类有效时间内，下架分类
            // 有一个时间条件匹配则上架分类，并跳出本次循环，不再继续执行
            if (in_array($whichWeek, $timingWeek) && ($timingStartTime < $nowTime && $nowTime < $timingEndTime)) {
                $status = 1;
                break;
            }
        } else {
            // 日期定时,方法同上
            if ($cTiming['day_start_time'] < $nowTime && $nowTime < $cTiming['day_end_time']) {
                $status = 1;
                break;
            }
        }
    }
    return $status;
}

/**
 * 验证密码是否符合规则
 * @param $password
 * @return int status
 */
function checkPwdRule($password)
{
    $msg = [];
    if (preg_match("/^\d*$/", $password)) {
        $msg['code'] = 0;
        $msg['msg'] = '密码必须包含字母';
    } else if (preg_match("/^[a-zA-Z]*$/i", $password)) {
        $msg['code'] = 0;
        $msg['msg'] = '密码必须包含数字';
    } else if (strlen($password) > 18 || strlen($password) < 6) {
        $msg['code'] = 0;
        $msg['msg'] = '密码必须为6-18位的字符串';
//    }else if(preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/i',$password)){
    } else {
        $msg['code'] = 1;
        $msg['msg'] = '密码包含数字和字母,强度:强';
    }
//    if (strlen($password)>18 || strlen($password)<6) {
//        $msg['code']    =   0;
//        $msg['msg']     =   '密码必须为6-18位的字符串';
//    }else{
//        if(preg_match("/^\d*$/",$password)){
//            $msg['code']    =   0;
//            $msg['msg']     =   '密码必须包含字母';
//        }else if(preg_match("/^[a-zA-Z]*$/i",$password)){
//            $msg['code']    =   0;
//            $msg['msg']     =   '密码必须包含数字';
//        }else if(preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/i',$password)){
//            $msg['code']    =   1;
//            $msg['msg']     =   '密码包含数字和字母,强度:强';
//        }
//    }
    return $msg;
}

/**
 * 清除菜单数据
 * @param int $business_id
 * @param int $food_model_id
 * @param int $resId 店铺id，要删除的店铺菜单,为0表示删除代理菜单
 * @return bool
 * */
function cleanFoodModel($business_id, $food_model_id, $resId = 0)
{
    $Model = M();
    $Model->startTrans();
    try {
        // 分类
        if (intval($resId) == 0) {
            $where['restaurant_id'] = '0';
        } else {
            $where['restaurant_id'] = $resId;
        }

        $where['business_id'] = $business_id;
        $where['food_model_id'] = $food_model_id;
        $categoryIds = $Model->table('food_category')->field('GROUP_CONCAT(category_id) as category_ids')->where($where)->find()['category_ids'];
        if ($categoryIds) {
            $whereCategory['category_id'] = ['in', $categoryIds];
            // 菜时分类
            $Model->table('food_category_timing')->where(["food_category_id" => ["in", $categoryIds]])->delete();
            // 清除菜品分类中间表关联数据
            $Model->table('food_restaurant_category')->where($whereCategory)->delete();
            // 清除菜品分类数据
            $Model->table('food_category')->where($whereCategory)->delete();
        }

        // 菜品数据与菜品中间表数据
        $foodIds = $Model->table('food')->field('GROUP_CONCAT(food_id) as food_ids')->where($where)->find()['food_ids'];

        if ($foodIds) {
            $whereFoodIds['food_id'] = ['in', $foodIds];
            $foodResIds = $Model->table('food_restaurant')->field('GROUP_CONCAT(food_restaurant_id) as food_restaurant_ids')->where($whereFoodIds)->find()['food_restaurant_ids'];

            if ($foodResIds) {
                $whereFoodResIds['food_restaurant_id'] = ['in', $foodResIds];
                // 自定义规格
                $specIds = $Model->table('food_specification')->field('GROUP_CONCAT(food_specification_id) as food_specification_id')->where($whereFoodResIds)->find()['food_specification_id'];
                if ($specIds) {
                    $whereSpec['food_specification_id'] = ['in', $specIds];
                    // 规格属性
                    $specTypeIds = $Model->table('food_specification_middle')->alias('fsm')
                        ->field('GROUP_CONCAT(fst.food_type_id) as food_type_id')
                        ->join('food_specification_type fst on fsm.food_type_id = fst.food_type_id')
                        ->where($whereSpec)
                        ->find()['food_type_id'];
                    if ($specTypeIds) {
                        // 清除规格属性
                        $Model->table('food_specification_type')->where(['food_type_id' => ['in', $specTypeIds]])->delete();
                    }
                    // 清除规格关联数据
                    $Model->table('food_specification_middle')->where($whereSpec)->delete();

                    // 清除规格数据
                    $Model->table('food_specification')->where($whereSpec)->delete();
                }
                // 清除菜品中间表数据
                $Model->table('food_restaurant')->where($whereFoodResIds)->delete();
            }
            // 清除菜品数据
            $Model->table('food')->where($whereFoodIds)->delete();
        }
        $Model->commit();
        return true;
    } catch (\Exception $e) {
        $Model->rollback();
        return false;
    }
}


/**
 * 规格数组拆分，用于处理导入菜单
 * 规格数据量过大，逐条添加成本过高，处理成大数组批量插入
 * @param int   maxSpecTypeId   规格起始id
 * @param int   specId          规格id
 * @param array specType        规格属性数组
 * @return boolean
 * */
function specTypeFormat(&$maxtypeId, $specId, $specTypeVal, &$specResult)
{

    if (!$specTypeVal) return;

    try {

        foreach ($specTypeVal as $specTye_key => $specType) {

            $sTypeInfo = explode('_', $specType);

            // 自增规格属性id
            $maxtypeId++;

            // 规格属性
            // 荷包蛋_3.00_ZS0002_imgurl_egg
            // 分别是:名称 - 价格 - 属性码 - 图片url - 英文名称
            $specTypeInfo[$specTye_key]['food_type_name'] = $sTypeInfo[0];
            $specTypeInfo[$specTye_key]['plus_price'] = $sTypeInfo[1];
            $specTypeInfo[$specTye_key]['type_vcode'] = $sTypeInfo[2];
            $specTypeInfo[$specTye_key]['type_img'] = $sTypeInfo[3];
            $specTypeInfo[$specTye_key]['type_english_name'] = $sTypeInfo[4];
            $specTypeInfo[$specTye_key]['food_specification_id'] = $specId;
            $specTypeInfo[$specTye_key]['food_type_id'] = $maxtypeId;

        }

        $specResult[] = $specTypeInfo;

        return $specResult;

    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 商品操作增量记录
 * @param int $record_id
 * @param int $business_id 代理id
 * @param int $restaurant_id 店铺id
 * @param int $record_type 增量记录类型:1-菜品,2-菜品中间数据,3-菜品分类,4-菜品分类中间数据,5-规格,6-规格属性
 * @param int $operate_type 操作类型:1- 添加 2-修改 3-删除
 * @return bool
 */
function setIncrementalQuantity($record_id, $business_id, $restaurant_id, $record_type, $operate_type)
{

    $result = false;

    if (!$record_id) {
        return $result;
    }

    $data = [
        'record_id' => $record_id,
        'business_id' => $business_id,
        'restaurant_id' => $restaurant_id,
        'record_type' => $record_type,
        'operate_type' => $operate_type,
        'create_time' => time(),
    ];

    $incrementalQ = M('incremental_quantity');

    $record_id = $incrementalQ->where(['record_id' => $record_id, 'record_type' => $record_type])->getField('id');

    if ($record_id) {
        $result = $incrementalQ->where(['id' => $record_id])->save($data);
    } else {
        $result = $incrementalQ->add($data);
    }

    return $result;

}

// 年月order_coupon表
function order_C()
{
    // 带有年月后缀的订单券记录表的表名
    $yearMonth = date('Y') . date('m');
    return M('order_coupon_' . $yearMonth);
}

// 年月order_voucher表
function order_V()
{
    // 带有年月后缀的订单凭单表的表名
    $yearMonth = date('Y') . date('m');
    return M('order_voucher_' . $yearMonth);
}

/**
 * 指定保留数字的小数点后多少位 进一取整
 * @param float $number 需要处理的数字
 * @param int $saveNumber 保留小数点后几位
 * @return float 处理后的数字
 */
function decimalFromCeil($number, $saveNumber)
{
    $digit = pow(10, $saveNumber);
    return round(ceil($number * $digit) / $digit, $saveNumber);
}


/**
 * @param $code 状态码
 * @param $msg 提示语
 * @param $data 返回数据
 * @return false|string 接口返回方法
 */
function return_result($code, $msg, $data = [], $type = 'JSON')
{

    $now = date('U');
    $data = [
        'time' => $now,
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ];

    switch (strtoupper($type)) {
        case 'JSON' :
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode($data));
        case 'XML'  :
            // 返回xml格式数据
            header('Content-Type:text/xml; charset=utf-8');
            exit(xml_encode($data));
        case 'JSONP':
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
            exit($handler . '(' . json_encode($data) . ');');
        case 'EVAL' :
            // 返回可执行的js脚本
            header('Content-Type:text/html; charset=utf-8');
            exit($data);
    }
}

//自定义请求接口函数，$data为空时发起get请求，$data有值时发起post请求
function http_url($url, $data = null)
{

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if (!empty($data)) {

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    }

    $res = curl_exec($ch);

    if (curl_errno($ch)) {

        echo "error:" . curl_error($ch);

        exit;

    }

    curl_close($ch);

    return $res;

}


/** 递归删除文件
 * @param $dirName
 * @param bool $subdir
 */
function delDirAndFile($dirName, $subdir = true)
{
    if ($handle = opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item"))
                    delDirAndFile("$dirName/$item", false);
                else
                    @unlink("$dirName/$item");
            }
        }
        closedir($handle);
        if (!$subdir) @rmdir($dirName);
    }
}


/**
 * curl post请求
 * @param  [type]  $url    [description]
 * @param  [type]  $data   [字符串或者数组]
 * @param array $header [description]
 * @param integer $post [description]
 * @return [type]          [description]
 */
function curl_post($url, $data, $header = [], $post = 1)
{

    $is_ssl = stripos($url, 'https') === 0;
    //初始化curl
    $ch = curl_init(); //初始化curl
    $str_agent = 'Mozilla/5.0 \(Windows; U; Windows NT 5.1; rv:1.7.3\) Gecko/20041001 Firefox/0.10.1';
    curl_setopt($ch, CURLOPT_USERAGENT, $str_agent);
    //参数设置
    $res = curl_setopt($ch, CURLOPT_URL, $url); //抓取指定网页
    if ($is_ssl) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
    curl_setopt($ch, CURLOPT_POST, $post); //post提交方式
    if ($post) {
        $data = is_array($data) ? http_build_query($data) : $data;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $result = curl_exec($ch);
    //连接失败
    // if ($result == false) {
    //     $result = "网络错误";
    // }
    curl_close($ch);
    return $result;
}

/**
 * 获取Redis对象
 * @param integer $db 选择DB
 * @return Object
 */
function getRedis($db = 0)
{
    $options = [];
    $options['host'] = C('REDIS.HOST'); // ip  xxx.xxx.xxx.xxx
    $options['port'] = C('REDIS.PORT'); // 端口号 6379
    $options['auth'] = C('REDIS.AUTH'); // 端口号 6379

    file_put_contents ( "./saoma1.log", date ( "Y-m-d H:i:s" ) . "  " . json_encode($options));

    $attr = [];
    if ($db) $attr['db_id'] = $db; // 端口号 6379
    //创建一个redis对象
    $redis = \Vendor\Redis\Redis::getInstance($options, $attr);
    return $redis;
}

/**
 * 多token发送钉钉通知
 * @param string $content
 */
function tunnelSend($content)
{
//    $time = time();
//    $key = 'dingding:send:token:' . date('Ymd');
//    $userToken = '';
//    $groupTokens = C('DING.groupGokens');
//    /**
//     * groupGokens 中保存6个token，每个机器人每分钟可以发送18条消息
//     * 一共可以发送108条没分钟
//     */
//    if (!empty($groupTokens)) {
//        foreach ($groupTokens as $i => $token) {
//            $t = getRedis()->zrangebyscore($key . ':' . $i, $time - 60, $time);
//            if (count($t) > 18) {
//                continue;
//            }
//            $key .= ':' . $i;
//            $userToken = $token['type'];
//            break;
//        }
//        if (!$userToken) {
//            // 如果还有未发的消息，可以使用别的进程处理队列中的数据
//            getRedis()->sAdd('ding:ding:send:list:' . date('Ymd'), $content, 604800); // 七天
//        }
//        getRedis()->zAdd($key, $time, microtime(true)); // 添加一次请求记录
//        $ding = new \DingNotice\DingTalk([
//            "default" => C('DING.'.$userToken)
//        ]);
//        $ding->text($content);
//    }
}

/**
 * 成功
 * @param string $msg
 * @param array $data
 */
function successJson($msg = 'success', $data = [])
{
    $result['code'] = 1;
    $result['msg'] = $msg;
    if($data){
        $result['data'] = $data;
    }
    echo json_encode($result,JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 失败
 * @param string $msg
 */
function failJson($msg = 'fail')
{
    $result['code'] = 0;
    $result['msg'] = $msg;
    echo json_encode($result,JSON_UNESCAPED_UNICODE);
    exit;
}
/**
 * 更新会员余额
 * $id 会员ID
 * $remainder  变动金额
 * $order_sn   订单编号
 * $log_type   1充值， 2支付
 * transaction_sn 支付订单号
 * desc   备注
 */
function set_vip_remainder($id, $remainder, $log_type=1, $order_sn='', $transaction_sn='', $desc=''){
    $info = D("members")->field('id,money, merchant_id')->where(['id'=> $id])->find();
    // 变动用户余额
    // if($log_type == 1 || $log_type == 6){
    //     $vipdata['money'] = ['exp', 'money+'.$remainder];
    // }else{
    //     $vipdata['money'] = ['exp', 'money-'.$remainder];
    // }
    $vipdata['money'] = bcadd($remainder,$info['money']);
    $res = D("members")->where(['id'=> $id])->save($vipdata);
    // 插入用户余额变动表
    $log['member_id'] = $id;
    $log['merchant_id'] = $info['merchant_id'];
    $log['order_sn'] = $order_sn;
    $log['desc'] = $desc;
    $log['change_type'] = $log_type;
    $log['create_at'] = time();
    $log['amount'] = $remainder;
    $log['before_change_amount'] = $info['money'];
    $log['after_change_amount'] = bcadd($remainder,$info['money']);
    $log['transaction_sn'] = $transaction_sn;
    $ress = D("account_log")->add($log);
    if(!$res){
        $add['order_sn'] = $order_sn;
        $add['problem_table'] = "vip";
        D("prepaid_callback_fail")->add($add);
    }

}