<?php
namespace Mobile\Controller;

header("Content-type: text/html; charset=utf-8");
use data\service\Order;
use data\service\Push as ServicePush;
use data\service\Restaurant;
use Think\Controller;
use \Push\Request\V20160801 as Push;

Vendor("ali_push.aliyun-php-sdk-core.Config");
Vendor("ali_push.Push.Request.V20160801.PushRequest");
Vendor("ali_push.Push.Request.V20160801/PushMessageToAndroidRequest");
Vendor("ali_push.Push.Request.V20160801/QueryPushStatByMsgRequest");

class IndexController extends Controller
{
    public function checkwate($restaurant_id)
    {
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        // dump(session());die;
        if (!isset($_GET['code'])) {
            if (session('desk_code')) {
                $url = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/index/index/restaurant_id/" . $restaurant_id . "/desk_code/" . session('desk_code'));
                Header("Location: $url");
                exit;
            } else {
                $url = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/index/homePage/restaurant_id/" . $restaurant_id);
                Header("Location: $url");
                exit;
            }
        }
        //=========步骤1：网页授权获取用户openid============
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();

        $configModel                = D('config');
        $condition['config_type']   = "wxpay";
        $condition['restaurant_id'] = $restaurant_id;
        $wxpay_config               = $configModel->where($condition)->field("config_name,config_value")->select();
        // dump($wxpay_config);
        $wxpay_c = dealConfigKeyForValue($wxpay_config);

        $appid     = $wxpay_c['wxpay_appid'];
        $appsecret = $wxpay_c['wxpay_appsecret'];

        //获取调用接口凭证
        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $access_msg   = json_decode(file_get_contents($access_token));
        $token        = $access_msg->access_token;
        //获取用户是否订阅了公众号
        $subscribe_msg = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid";
        $subscribe     = json_decode(file_get_contents($subscribe_msg));
        $gzxx          = $subscribe->subscribe;

        //判断并返回数据
        if ($gzxx === 1) {
            $returnData['code'] = 1;
            $returnData['msg']  = '已关注';
            // exit(json_encode($returnData));
            return $returnData;
        } else {
            $returnData['code'] = 0;
            $returnData['msg']  = '未关注';
            $url                = M('wechat')->where(array('restaurant_id =' . session('restaurant_id')))->field('url')->find();

            $returnData['data'] = $url['url'];
            return $returnData;
        }

    }

    // 订单页
    public function index_old()
    {
        session("restaurant_id", I("get.restaurant_id"));
        session("desk_code", I("get.desk_code"));
        if (empty(I("get.desk_code"))) {
            session("desk_code", null);
        }
//不存在桌子号则删除

        $restaurant_id = session("restaurant_id");

        $food_category              = D('food_category');
        $category_time              = D('category_time');
        $condition['restaurant_id'] = $restaurant_id;
        $condition['is_timing']     = 0;
        $arr                        = $food_category->where($condition)->order('sort asc')->select();
        $where['restaurant_id']     = session('restaurant_id');
        $where['is_timing']         = 1;
        $food_categoryIdList        = $food_category->where($where)->field('food_category_id')->select();
        if ($food_categoryIdList) {
            $food_categoryNewIdList = array();
            foreach ($food_categoryIdList as $foodvv) {
                $food_categoryNewIdList[] = $foodvv['food_category_id'];
            }

            //第一种时间段的查询
            $current_time               = time();
            $t_condition['time1']       = array("lt", $current_time);
            $t_condition['time2']       = array("gt", $current_time);
            $t_condition['category_id'] = array("in", $food_categoryNewIdList);
            $category_ids               = $category_time->where($t_condition)->distinct("category_id")->field("category_id")->select();
            if ($category_ids) {
                $category_id_list = array();
                foreach ($category_ids as $k => $v) {
                    $index                    = "cid" . $v['category_id'];
                    $category_id_list[$index] = $v['category_id'];
                }
            }

            //第二种星期段的查询
            $current_week                      = date("w");
            $ftg_condition['timing_day']       = array("like", "%" . $current_week . "%");
            $ftg_condition['food_category_id'] = array("in", $food_categoryNewIdList);
            $category_timing_model             = D("food_category_timing");
            $category_ids2                     = $category_timing_model->where($ftg_condition)->distinct("food_category_id")->field("food_category_id,start_time,end_time")->select();

            $category_id_list2 = array();
            if ($category_ids2) {
                foreach ($category_ids2 as $kk => $vv) {
                    $start_time = strtotime($vv['start_time']);
                    $end_time   = strtotime($vv['end_time']);
                    if ($start_time < $current_time && $end_time > $current_time) {
                        $index                     = "cid" . $vv["food_category_id"];
                        $category_id_list2[$index] = $vv["food_category_id"];
                    }
                }
            }

            //合并两种情况下的分类ID
            if ($category_id_list == null) {
                $categoryIdsList = $category_id_list2;
            } else if ($category_id_list2 == null) {
                $categoryIdsList = $category_id_list;
            } else {
                $categoryIdsList = array_merge($category_id_list, $category_id_list2);
            }

            $lastCategoryIdsList = array();
            foreach ($categoryIdsList as $vvv) {
                $lastCategoryIdsList[] = $vvv;
            }

            if ($lastCategoryIdsList) {
                $l_condition['food_category_id'] = array("in", $lastCategoryIdsList);
                $arr2                            = $food_category->where($l_condition)->select();
                $arr                             = array_merge($arr, $arr2);
            }
        }

        $sortArr    = array();
        $food_infos = array();
        foreach ($arr as $key => $v1) {
            $sortArr[] = $v1['sort'];

            $foodUnderCate                       = $this->layzLoad($v1['food_category_id']);
            $food_infos[$v1['food_category_id']] = $foodUnderCate;
        }
        array_multisort($sortArr, SORT_ASC, $arr);

        // 返回所需数据
        //            $return_data['info'] = $arr;
        //            $return_data['food_infos'] = $food_infos;
        //            print_r($return_data);
        //            return $return_data;
        $this->assign("info", $arr);
        $this->assign("food_infos", $food_infos);
        $this->display();
    }

    //微信的预加载首页
    public function homePage()
    {
        $restaurant_id = I("get.restaurant_id");
        $business_id   = I("get.business_id");
        $pay_status    = I("get.pay_status");
        // dump(session());
        // dump($_GET['code']);die;
        // if(empty($pay_status))//判断支付状态，默认是在线支付 在线支付online 预点餐preparation
        // {
        //     if(empty($_SESSION['pay_status']))//判断是否是订单首页点击返回首页
        //     {
        //        $_SESSION['pay_status']='online';
        //     }
        // }
        // else{
        //     $_SESSION['pay_status']=$pay_status;
        // }
        if (!empty($pay_status)) {
            $_SESSION['pay_status'] = $pay_status;
        }
        if ($pay_status == 'online' || $pay_status == 'preparation') {
            session('desk_code', null);
        }

        if (!empty($restaurant_id)) {
            //判断是从店铺进入还是代理进入
            $_SESSION['restaurant_id'] = $restaurant_id;
            $where['restaurant_id']    = $restaurant_id;
            $info                      = M('restaurant')->where($where)->field('restaurant_name,logo,address,business_id')->find();
            $businessType              = M('business')->where(array('business_id' => $info['business_id']))->getField('type');
            $business_id               = $info['business_id'];
            $this->assign("info", $info);
            $show = 1; //需要显示h5代码
            $this->assign("show", $show);
        } else {
            $businessType = M('business')->where(array('business_id' => $business_id))->getField('type');
        }
        // session('is_data', null);
        // //dump(session());
        // if (empty(session('is_data'))) {

        //     $returnData = $this->checkwate($restaurant_id);
        //     if ($returnData) {
        //         session('is_data', 'yes');
        //     }
        //     $this->assign('returnData', $returnData);
        // }
        $host = C('HOST_NAME');
        if ($businessType == 1 || I("get.business_id")) {
            //1为多店铺模式
            $url['diancan'] = $host . "/index.php/Mobile/AgentWeixin/index?type={$businessType}&business_id={$business_id}";
            $url['myorder'] = $host . "/index.php/mobile/order/index?business_id={$business_id}";
        } else {
            $url['diancan'] = $host . "/index.php/mobile/index/index/restaurant_id/$restaurant_id";
            $url['myorder'] = $host . "/index.php/mobile/order/index/restaurant_id/$restaurant_id";
        }
        if (session('desk_code')) {
            $this->assign('desk_code', session('desk_code'));
        }
        $this->assign("url", $url);
        $this->display('homePage');
    }

    public function index()
    {
        // dump(I('get.desk_code'));
        session("restaurant_id", I("get.restaurant_id"));
        session("desk_code", I("get.desk_code"));
        cookie('restaurant_id', I("get.restaurant_id"), 1296000); //店铺id默认缓存15天
        $desk_code = I("get.desk_code");
        if (empty($desk_code)) {
            session("desk_code", null);
        }
//不存在桌子号则删除
        $pay_status = I("get.pay_status");
        $order_sn   = I("get.order_sn");
        if (empty($pay_status)) //判断支付状态，默认是在线支付 在线支付online 预点餐preparation
        {
            if (empty($_SESSION['pay_status'])) //判断是否是订单首页点击返回首页
            {
                $_SESSION['pay_status'] = 'online';
            }
        } else {
            $_SESSION['pay_status'] = $pay_status;
        }

        //if(!empty($desk_code)) $this->redirect('Index/pay_old', 'order_sn='.$order_sn);
        if ($_SESSION['pay_status'] == 'online' && !empty($order_sn)) {
            $restaurant_id = order()->where(array('order_sn' => $order_sn))->getField('restaurant_id');
            $desk_code     = order()->where(array('order_sn' => $order_sn))->getField('desk_code');
            // echo order()->getLastSql();
            // dump($desk_code);die;

            session("restaurant_id", $restaurant_id);

            cookie('restaurant_id', $restaurant_id, 1296000); //店铺id默认缓存15天
            $this->isRestaurantType();
            $S_order  = new Order();
            $timeInfo = $S_order->getSetTimeInfo(1);
            //$desk_code=I("get.desk_code");
            //dump($_SESSION);
            //dump($desk_code);
            //        if(empty($timeInfo['ext']) || !empty($desk_code)) $this->redirect('Index/pay_old', 'order_sn='.$order_sn);
            if (!empty($desk_code)) {
                $this->redirect('Index/pay_old', 'order_sn=' . $order_sn);
            }

            vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
            $jsApi = new \JsApi_pub();
            if (!isset($_GET['code'])) {
                $url = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/index/index/order_sn/" . $order_sn);
                Header("Location: $url");
                exit;
            }

            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
            $this->payConfigLoad($order_sn, $openid);
            $this->assign('order_sn', $order_sn);

        }
        $this->display();
    }

    // 菜品主页页面加载完后ajax请求
    public function ajaxGetFoodInfo()
    {
//        $_SESSION['restaurant_id'] = 131;
        $food_category = D('food_category');
        $return_data   = $food_category->getAllFoodInfo(); //优化前
        //$return_data = $this->index_old();//优化后
        $this->ajaxReturn($return_data);

    }

    // 菜品分类下的菜品，用于懒加载
    public function layzLoad($food_category_id)
    {
        //$system_time1=time();

        $condition['food_category_id'] = $food_category_id;
        $food_category_relative        = D('food_category_relative');
        $arr                           = $food_category_relative->where($condition)->select();
        //dump($arr);
        $food    = D('food');
        $arrlist = array();
        //dump($arr);
        //优化代码测试

        $food_id_arr = array();
        foreach ($arr as $v) {
            $food_id_arr[] = $v['food_id'];
        }
        $food_id_str = implode(",", $food_id_arr);
        $start       = mktime(0, 0, 0, date("m"), date("d"), date("Y")); //当天开启时间
        $end         = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1; //当天结束时间
        $Model       = M();
        $sql         = "select t1.food_num,t1.food_id from order_food t1 LEFT JOIN
                        `order` t2 on t1.order_id = t2.order_id
                                                WHERE t1.food_id in ($food_id_str) and t2.order_status in ('3','11','12')
                       and t2.add_time between $start and $end";
        $res = $Model->query($sql);
        //优化代码测试结束
        //判断菜品是否售完
        $food_id_num_arr = array();
        foreach ($res as $food_id_key => $food_id_value) {
            if (isset($food_id_num_arr[$food_id_value['food_id']])) {
                $food_id_num_arr[$food_id_value['food_id']] += $food_id_value['food_num'];
            } else {
                $food_id_num_arr[$food_id_value['food_id']] = $food_id_value['food_num'];
            }
        }
        $fit_num     = D("food")->where(array("food_id" => array('in', $food_id_arr)))->field("food_id,foods_num_day")->select();
        $fit_num_arr = array();
        foreach ($fit_num as $fit_num_key => $fit_num_value) {
            $fit_num_arr[$fit_num_value['food_id']] = $fit_num_value['foods_num_day'];
        }
        foreach ($fit_num_arr as $fit_num_arr_key => $fit_num_arr_value) {
            if (!isset($food_id_num_arr[$fit_num_arr_key])) {
                continue;
            }
            if ($food_id_num_arr[$fit_num_arr_key] >= $fit_num_arr[$fit_num_arr_key]) {
                unset($fit_num_arr[$fit_num_arr_key]);
            }
        }

        $fit_num_arr_food_id_arr = array();
        foreach ($fit_num_arr as $fit_num_arr_keys => $fit_num_arr_values) {
            $fit_num_arr_food_id_arr[] = $fit_num_arr_keys;
        }
        if (count($fit_num_arr_food_id_arr) == 0) {
            return;
        }
        //判断菜品是否上架
        $condition1['food_id']       = array('in', $fit_num_arr_food_id_arr);
        $condition1['restaurant_id'] = session("restaurant_id");
        $condition1['is_sale']       = 1;
        $food_sale                   = $food->where($condition1)->select();
        //区分打折的菜品
        $food_prom             = array();
        $food_prom_arr         = array();
        $food_attribute_id_arr = array();
        $is_food_prom          = false;
        //判断是否打折
        foreach ($food_sale as $food_sale_key => $food_sale_value) {
            $food_prom_arr[$food_sale_value['food_id']] = $food_sale_value;
            $food_attribute_id_arr[]                    = $food_sale_value['food_id'];
            if ($food_sale_value['is_prom'] == 0) {

                continue;
            }
            $food_prom[]  = $food_sale_value['food_id'];
            $is_food_prom = true;
        }
        //将打折的后的价格覆盖正常价格
        if ($is_food_prom) {
            $prom                      = D('prom');
            $where2['prom_id']         = array('in', $food_prom);
            $when_time                 = time();
            $where2['prom_start_time'] = array("lt", $when_time);
            $where2['prom_end_time']   = array("gt", $when_time); //   prom_start_time<when_time<prom_end_time
            $food_prom_price           = $prom->where($where2)->field('prom_id,prom_price')->select();
            $food_prom_price_arr       = array();
            foreach ($food_prom_price as $food_prom_price_key => $food_prom_price_value) {
                $food_prom_price_arr[$food_prom_price_value['prom_id']] = $food_prom_price_value['prom_price'];
            }
            foreach ($food_prom_price_arr as $food_prom_price_arr_key => $food_prom_price_arr_value) {
                $food_prom_arr[$food_prom_price_arr_key]['food_price'] = $food_prom_price_arr_value;
            }
        }
        // 该菜品是否有属性
        $food_attribute_id_str = implode(",", $food_attribute_id_arr);
        $sql                   = "SELECT t1.food_id  AS total_num FROM attribute_type AS t1 RIGHT JOIN food_attribute AS t2 ON t1.attribute_type_id = t2.attribute_type_id WHERE t1.food_id in($food_attribute_id_str)";
        $food_attribute        = $Model->query($sql);
        $food_attribute_num    = array();
        foreach ($food_attribute as $food_attribute_key => $food_attribute_value) {
            if (!isset($food_attribute_num[$food_attribute_value['total_num']])) {
                $food_attribute_num[$food_attribute_value['total_num']] = 1;
            } else {
                $food_attribute_num[$food_attribute_value['total_num']]++;
            }
        }
        //排序和赋值属性数量
        $sortArr = array();
        foreach ($food_prom_arr as $food_prom_arr_key => $food_prom_arr_value) {
            if (!isset($food_attribute_num[$food_prom_arr_key])) {
                $food_prom_arr[$food_prom_arr_key]['have_attribute'] = 0;
            } else {
                $food_prom_arr[$food_prom_arr_key]['have_attribute'] = $food_attribute_num[$food_prom_arr_key];

            }
            $sortArr[] = $food_prom_arr_value['sort'];
        }
        array_multisort($sortArr, SORT_ASC, $food_prom_arr);
        //用于排序
        return $food_prom_arr;
    }

    // 点击+按钮查看菜品详情
    public function findfoodinfo()
    {
        session("restaurant_id", I("get.restaurant_id"));
        session("desk_code", I("get.desk_code"));
        $food                 = D('food');
        $condition['food_id'] = I('get.food_id');
        $is_prom              = $food->where($condition)->field('is_prom')->find()['is_prom'];
        $food_price           = $food->where($condition)->field('food_price')->find()['food_price'];
        $prom                 = D('prom');
        if ($is_prom == 1) {
            $where2['prom_id']         = I('get.food_id');
            $when_time                 = time();
            $where2['prom_start_time'] = array("lt", $when_time);
            $where2['prom_end_time']   = array("gt", $when_time);
            $prom_price                = $prom->where($where2)->field('prom_price')->find()['prom_price'];
            if ($prom_price) {
                $prom_price = $prom_price;
            } else {
                $prom_price = $food_price;
            }
        } else {
            $prom_price = $food_price;
        }

        $this->assign("food_price", $prom_price);

        $arr = $food->where($condition)->field("food_id,food_name,food_img,food_desc")->find();
        $this->assign("info3", $arr);

        $attribute_type          = D('attribute_type');
        $at_condition['food_id'] = $arr['food_id'];
        $at_list                 = $attribute_type->where($at_condition)->field('attribute_type_id,type_name,select_type')->select();
        $food_attribute          = D('food_attribute');

        foreach ($at_list as $k => $v) {
            $fa_condition['attribute_type_id'] = $v['attribute_type_id'];
            $f_attr                            = $food_attribute->where($fa_condition)->field("food_attribute_id,attribute_name,attribute_price")->select();

            foreach ($f_attr as $fok => $fov) {
                $length = strlen($fov["attribute_name"]);
                if ($length <= 12) {
                    $f_attr[$fok]['length_type'] = "attr-sm";
                } elseif ($length > 12) {
                    $f_attr[$fok]['length_type'] = "attr-lg";
                }
            }

            $at_list[$k]["attrs"] = $f_attr;
        }
        $this->assign("at_list", $at_list);

        $this->display('orderPopup');
    }

    //显示分类菜品信息
    public function showtypefood($type = 0)
    {
        $food_category_relative        = D('food_category_relative');
        $food                          = D('food');
        $condition['food_category_id'] = $type;
        $arr                           = $food_category_relative->where($condition)->select();
        //dump($arr);
        $food    = D('food');
        $arrlist = array();
        //dump($arr);
        foreach ($arr as $v) {
            // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
            $start = mktime(0, 0, 0, date("m"), date("d"), date("Y")); //当天开启时间
            $end   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1; //当天结束时间

            $Model = M(); // 实例化一个model对象 没有对应任何数据表
            $num   = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $v[food_id] and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end");

            if ($num) {
                // 当天到目前为止消费数量
                $sum = 0;
                foreach ($num as $n) {
                    $sum += $n['num'];
                }
                // 查询出该food_id对应多少限额
                $fit_num = D("food")->where(array("food_id" => $v['food_id']))->getField("foods_num_day");
                if ($sum >= $fit_num) {
                    continue;
                }
            }

            $condition1['food_id']       = $v['food_id'];
            $condition1['restaurant_id'] = session("restaurant_id");
            $condition1['is_sale']       = 1;
            $result                      = $food->where($condition1)->find();
            if ($result) {
                if ($result['is_prom'] == 1) {
                    $prom                      = D('prom');
                    $where2['prom_id']         = $v['food_id'];
                    $when_time                 = time();
                    $where2['prom_start_time'] = array("lt", $when_time);
                    $where2['prom_end_time']   = array("gt", $when_time); //   prom_start_time<when_time<prom_end_time
                    $prom_price                = $prom->where($where2)->field('prom_price')->find()['prom_price'];
                    if ($prom_price) {
                        $result['food_price'] = $prom_price;
                    } else {
                        $result['food_price'] = $result['food_price'];
                    }
                } else {
                    $result['food_price'] = $result['food_price'];
                }
                // 该菜品是否有属性
                $have_attribute           = $Model->query('SELECT COUNT(*)  AS total_num FROM attribute_type AS t1 INNER JOIN food_attribute AS t2 ON t1.attribute_type_id = t2.attribute_type_id WHERE food_id = ' . $v['food_id']);
                $result['have_attribute'] = $have_attribute[0]['total_num'];
                $arrlist[]                = $result;
            }
        }
        //dump($arrlist);
        $this->assign("info2", $arrlist);
        $this->display('orderAjax');
    }
    /*
     *生成取餐柜核销验证码
     */
    public function getTakeNum()
    {
        $arr = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
        $str = '';
        for ($i = 0; $i < 5; $i++) {
            $str .= $arr[rand(0, 9)];
        }
        $S_Order              = new Order();
        $where['cancell_num'] = $str;
        $num                  = $S_Order->getCount($where);
        if ($num > 0) {
            $this->getTakeNum();
        }

        return $str;
    }
    // 下单
    public function PlaceOrder()
    {
        // dump(session());
        $order = order();
        $order->startTrans();
        $e_arr = I('post.');

        $arr = array();
        foreach ($e_arr as $e_k => $e_v) {
            $temp['food_id']    = $e_v[0];
            $temp['food_num']   = $e_v[1];
            $temp['food_attr']  = str_replace("-", "|", $e_v[2]);
            $temp['order_type'] = I("get.order_type");
            $arr[]              = $temp;
        }

        $arraylist  = array(); //单价数组
        $totallist  = array(); //属性价数组
        $numberlist = array(); //份数数组

        $food           = D('food');
        $food_attribute = D('food_attribute');

        foreach ($arr as $v) {
            $attlist          = array();
            $food_attr_string = $v['food_attr'];
            $arr1             = explode('|', $food_attr_string, -1);

            foreach ($arr1 as $v1) {
                $condition['food_attribute_id'] = (int) $v1;
                $att                            = $food_attribute->where($condition)->field('attribute_price')->find();
                $att                            = $att['attribute_price'];
                $attlist[]                      = $att;
            }
            $atttotal = array_sum($attlist);

            $totallist[]      = $atttotal;
            $where['food_id'] = $v['food_id'];
            $is_prom          = $food->where($where)->field('is_prom')->find()['is_prom'];
            $foodlist         = $food->where($where)->field('food_price')->find()['food_price'];
            if ($is_prom == 1) {
                $prom                      = D('prom');
                $where2['prom_id']         = $v['food_id'];
                $when_time                 = time();
                $where2['prom_start_time'] = array("lt", $when_time);
                $where2['prom_end_time']   = array("gt", $when_time);
                $prom_price                = $prom->where($where2)->field('prom_price')->find()['prom_price'];
                $foodlist                  = $prom_price;
            } else {
                $foodlist = $foodlist;
            }
            $foodlist     = $foodlist;
            $arraylist[]  = (float) $foodlist;
            $numberlist[] = (int) $v['food_num'];
        }
        //var_dump($totallist);
        //var_dump($arraylist);
        //var_dump($numberlist);
        $aLen = count($totallist);
        $bLen = count($arraylist);
        if ($aLen > $bLen) {
            $len = $aLen;
        } else {
            $len = $bLen;
        }
        $c = array();
        for ($i = 0; $i < $len; $i++) {
            $c[] = $totallist[$i] + $arraylist[$i];
        }
        //var_dump($c);
        //单价与属性相加后的价格一维数组与数目相乘（对于坐标相乘）
        $dLen = count($c);
        $eLen = count($numberlist);
        if ($dLen > $eLen) {
            $len = $dLen;
        } else {
            $len = $eLen;
        }
        $f = array();
        for ($i = 0; $i < $len; $i++) {
            $f[] = $c[$i] * $numberlist[$i];
        }

        $foodtotal = array_sum($f);

        $start                       = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end                         = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $condition1['add_time']      = array("between", array($start, $end));
        $condition1['restaurant_id'] = session("restaurant_id");

        $num      = $order->where($condition1)->count();
        $order_sn = "DC" . str_pad(session('restaurant_id'), 5, "0", STR_PAD_LEFT) . date("ymdHis", time()) . str_pad($num + 1, 5, "0", STR_PAD_LEFT); //订单号，$num+1表示最新一订单

//        file_put_contents(__DIR__."/"."placeOrder_num.txt",'|订单号:'.$order_sn."||店铺ID：".session("restaurant_id")."||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);

        $add_time                     = time(); //下单时间
        $total_amount                 = $foodtotal; //订单总价
        $condition2['order_sn']       = $order_sn; //订单号
        $condition2['add_time']       = $add_time; //下单时间
        $condition2['total_amount']   = $total_amount; //订单总价
        $condition2['original_price'] = $total_amount; //订单原价
        $condition2['table_num']      = $arr[0]['table_num'] ? $arr[0]['table_num'] : 000; //餐桌号
        $condition2['desk_code']      = session("desk_code");
        $condition2['is_request']     = 0; //订单标识
        $desk_code                    = session("desk_code");
        if (empty($desk_code) && session('pay_status') != 'online') {
            $condition2['is_reserve']  = 1; //是否为预定
            $condition2['openid']      = session("openid");
            $condition2['take_num']    = $this->getTakeNum(); //取餐验证码
            $condition2['push_status'] = 1; //预点餐取餐状态
        } else {
            $condition2['is_reserve']  = 0; //是否为预定
            $condition2['openid']      = session("openid");
            $condition2['take_num']    = $this->getTakeNum(); //取餐验证码
            $condition2['push_status'] = 1; //点餐取餐状态
        }
        $condition2['restaurant_id']   = session("restaurant_id");
        $S_Restaurant                  = new Restaurant();
        $restaurant_info               = $S_Restaurant->getInfo();
        $condition2['restaurant_name'] = $restaurant_info['restaurant_name'];
        if ($arr[0]['order_type']) {
            $condition2['order_type'] = $arr[0]['order_type'];
        } else {
            $condition2['order_type'] = 1;
        }
        $condition2['terminal_order'] = 3;

        $condition2['related_user'] = $_SESSION['openid'];
        $result                     = $order->data($condition2)->add();

        if (!$result) {
            $order->rollback();
//            exit;
            $returnData["code"] = 0;
            $returnData["msg"]  = "下单失败（add_order）";
            exit(json_encode($returnData));
        }
        $order_food             = order_F();
        $food                   = D('food');
        $condition3['order_id'] = $result;

        $order_food_attribute = order_F_A();
        foreach ($arr as $v2) {
            $attlist1                = array();
            $condition3['food_id']   = $v2['food_id'];
            $food1                   = $food->where("food_id=" . $v2['food_id'])->find();
            $condition3['food_name'] = $food1['food_name'];
            $condition3['food_num']  = $v2['food_num'];
            $food_attr_string1       = $v2['food_attr'];
            $arrz                    = explode('|', $food_attr_string1, -1);
            foreach ($arrz as $v1) {
                $condition7['food_attribute_id'] = (int) $v1;
                $att1                            = $food_attribute->where($condition7)->field('attribute_price')->find();
                $att1                            = $att1['attribute_price'];
                $attlist1[]                      = $att1;
            }
            $atttotal1                 = array_sum($attlist1);
            $condition3['food_price2'] = (float) $atttotal1 + $food1['food_price'];

            $condition3['print_id']     = $food1['print_id'];
            $condition3['tag_print_id'] = $food1['tag_print_id'];

            $tmp_num = $v2['food_num'];
            for ($i = 1; $i <= $tmp_num; $i++) {
                $tmp_arr[$i - 1]['food_status'] = 1;
                $tmp_arr[$i - 1]['window_name'] = '';
            }
            $condition3['food_detail'] = json_encode($tmp_arr);
            /*********************添加数据时也要加上一个food_detail字段*******************/
            $result1 = $order_food->add($condition3);
            //数据插入成功后:删除tmp_arr数组
            unset($tmp_arr);
            if (!$result1) {
                $order->rollback();
//                exit;
                $returnData["code"] = 0;
                $returnData["msg"]  = "下单失败（add_orderFood）";
                exit(json_encode($returnData));
            }
            $food_attr_string1 = $v2['food_attr'];
            $arr2              = explode('|', $food_attr_string1, -1);
            if ($arr2[0] != 0) {
                foreach ($arr2 as $v3) {
                    if ($v3 == 0) {
                        $att1 = 0;
                        $att2 = 0;
                    } else {
                        $condition4['food_attribute_id'] = (int) $v3;
                        $att1                            = $food_attribute->where($condition4)->field('attribute_name')->find();
                        $att1                            = $att1['attribute_name'];
                        $att2                            = $food_attribute->where($condition4)->field('attribute_price')->find();
                        $att2                            = $att2['attribute_price'];
                    }
                    $p_condition5['food_attribute_id'] = (int) $v3;
                    $attr_id                           = $food_attribute->where($p_condition5)->field('attribute_type_id')->find()['attribute_type_id'];
                    if ($attr_id) {
                        $attribute_type_model = D("attribute_type");
                        $print_id             = $attribute_type_model->where("attribute_type_id = $attr_id")->field("print_id")->find()['print_id'];
                        $count_type           = $attribute_type_model->where("attribute_type_id = $attr_id")->field('count_type')->find()['count_type'];
                        $tag_print_id         = $attribute_type_model->where("attribute_type_id = $attr_id")->field('tag_print_id')->find()['tag_print_id'];
                    }
                    $condition5['food_attribute_name']  = $att1;
                    $condition5['food_attribute_price'] = $att2;
                    $condition5['print_id']             = $print_id;
                    $condition5['count_type']           = $count_type;
                    $condition5['order_food_id']        = $result1;
                    $condition5['tag_print_id']         = $tag_print_id;
                    $condition5['num']                  = $v2['food_num'];
                    $condition5['food_attribute_id']    = $v3;
                    $result2                            = $order_food_attribute->add($condition5);
                    if (!$result2) {
                        $order->rollback();
//                        exit;
                        $returnData["code"] = 0;
                        $returnData["msg"]  = "下单失败（add_orderFoodAttr）";
                        exit(json_encode($returnData));
                    }
                }
            }

        }
        //var_dump($result);
        //var_dump($result1);
        //var_dump($result2);
        $rel = $order->commit();
        if ($rel) {
            $r_data["order_sn"] = $order_sn;

            if ($_SESSION['pay_status'] == 'preparation') //判断支付形式
            {
                $returnData["code"] = 1;
            } else if ($desk_code) {
                $returnData["code"] = 3;
            } else {
                $returnData["code"] = 2;
            }

            //           if(!empty($desk_code)){//带有桌子号，即桌子号下单
            //             $returnData["code"] = 3;
            //           $returnData["desk_code"] = $desk_code;
            //     }
            $returnData["msg"]  = "下单成功";
            $returnData['data'] = $r_data;
            exit(json_encode($returnData));
        }
    }
    /*
     *判断单店铺还是多店铺情况，配置session值，支付api需通过session读配置
     */
    private function isRestaurantType()
    {
        session("restaurant_id", cookie('restaurant_id')); //赋值店铺id
        $S_Restaurant    = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();
        $business_info   = $S_Restaurant->getBusinessInfo($restaurant_info['business_id']);
        session("wx_prepaid_flag", 0);
        session("business_id", 0);
        if ($business_info['type'] == 1) {
//多店铺时读代理配置信息
            session("wx_prepaid_flag", 1);
            session("business_id", $restaurant_info['business_id']);
        }
        $this->assign("restaurant_id", session("business_id") ? 0 : session("restaurant_id"));
        $this->assign("restaurants_id", session("restaurant_id"));
        $this->assign("business_id", session("business_id"));
    }
    /*
     *选择订单就餐时间
     */
    public function selectEatTime()
    {
        $order_sn      = I("get.order_sn");
        $restaurant_id = order()->where(array('order_sn' => $order_sn))->getField('restaurant_id');
        session("restaurant_id", $restaurant_id);
        cookie('restaurant_id', $restaurant_id, 1296000); //店铺id默认缓存15天
        $this->isRestaurantType();
        $S_order   = new Order();
        $timeInfo  = $S_order->getSetTimeInfo(1);
        $desk_code = session("desk_code");
        if (empty($timeInfo['ext']) || !empty($desk_code)) {
            $this->redirect('Index/pay_old', 'order_sn=' . $order_sn);
        }

        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        if (!isset($_GET['code'])) {
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/Index/selectEatTime/order_sn/" . $order_sn);
            Header("Location: $url");
            exit;
        }
        // 获取code码，以获取openid
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();
        session('openid', $openid);
        $this->payConfigLoad($order_sn, $openid);
//        dump($timeInfo);
        $time = time() + $timeInfo['stop_ordering_time'] * 60;
        if ($timeInfo['types'] == 1) {
            //准时用餐
            // 过滤掉比当前小时：分钟小的数据
            $timeInfo['ext_tomo'] = $timeInfo['ext'];
            foreach ($timeInfo['ext'] as $key => $val) {
                if (strtotime($val['times']) < $time) {
                    unset($timeInfo['ext'][$key]);
                }
            }
        } else {
//自由用餐
            $time_arr = json_decode($timeInfo['business_hours'], true);

            $time_start_tomorrow = strtotime($time_arr['0']) + $timeInfo['add_order_time'] * 60;

            //以下是今天的时间,time1_today  time2_today
            //判断当前时间跟起始时间

            $time_now_add_order_time = time() + $timeInfo['add_order_time'] * 60;
//            $time_today_start = strtotime($time_arr['0']) + $timeInfo['add_order_time'] * 60;
            if (strtotime($time_arr['0']) > $time_now_add_order_time) {
                $start_time              = strtotime($time_arr['0']);
                $timeInfo['time1_today'] = date('H:i', $start_time);
            } else {
                $start_time              = time() + $timeInfo['add_order_time'] * 60;
                $timeInfo['time1_today'] = date('H:i', $start_time);
            }

            //判断当前时间跟结束时间
            $endtime = time() + $timeInfo['add_order_time'] * 60;
            if (strtotime($time_arr['1']) < $endtime) {
                $timeInfo['time2_today'] = 0;
            } else {
                $timeInfo['time2_today'] = $time_arr['1']; //结束时间
            }

            //以下time1,time2都是明天的时间
            $time_start_tomorrow = strtotime($time_arr['0']);
            $timeInfo['time1']   = date('H:i', $time_start_tomorrow); //明天的结束时间
            $timeInfo['time2']   = $time_arr['1']; //结束时间
        }
        $rel = order()->where(array("order_sn" => $order_sn))->field("total_amount")->find();
        // dump($rel);die;
        $set   = D("set");
        $where = array("restaurant_id" => session('restaurant_id'), "type" => 6); // type值为6即会员余额
        $open  = $set->where($where)->find();
        $this->assign('open', $open['if_open']);
        $this->assign('timeInfo', $timeInfo);
        $this->assign('order_sn', $order_sn);
        $this->assign("order", $rel);
        $this->display('selectEatTime');
    }
    /*
     *修改订单信息
     */
    public function updateOrder()
    {
        $data = I();
        if (empty($data['use_time'])) {
            $this->ajaxReturn(array('code' => 1, 'msg' => '请选择使用时间'));
        }

        $update_data = array(
            'use_day'  => empty($data['use_day']) ? 1 : $data['use_day'],
            'use_time' => $data['use_time'],
        );
        $where['order_sn'] = $data['order_sn'];
        $S_order           = new Order();
        $res               = $S_order->updateInfo($where, $update_data);
        if ($res) {
            $this->ajaxReturn(array('code' => 0, 'msg' => '操作成功', 'order_sn' => $data['order_sn']));
        }

        $this->ajaxReturn(array('code' => 1, 'msg' => '操作失败'));
    }
    /*
     *微支付配置预加载
     */
    public function payConfigLoad($order_sn, $openid)
    {
        $S_Order           = new Order();
        $where['order_sn'] = $order_sn;
        $order_info        = $S_Order->getPrimInfo($where);
        session('restaurant_id', $order_info['restaurant_id']);
        session('desk_code', $order_info['desk_code']);
        $qrc_condition['restaurant_id'] = $order_info['restaurant_id'];
        $qrc_code_id                    = M("qrc_code")->where($qrc_condition)->getField("qrc_code_id");
        $qrcd_condition['qrc_code_id']  = $qrc_code_id;
        $device_code                    = M("qrc_device")->where($qrcd_condition)->getField('qrc_device_code');
        $S_Restaurant                   = new Restaurant();
        $restaurant_info                = $S_Restaurant->getInfo();
        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $oy_where['restaurant_id'] = session("restaurant_id");
        $ordinary                  = D("pay_mode")->where($oy_where)->getField("mode");
        if ($ordinary == "3") {
//普通用户
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid", $openid);
            $unifiedOrder->setParameter("body", $restaurant_info['restaurant_name']); //商品描述
            $unifiedOrder->setParameter("out_trade_no", $order_sn); //商户订单号
            $unifiedOrder->setParameter("total_fee", $order_info['total_amount'] * 100); //总金额
            if ($device_code) {
                $unifiedOrder->setParameter("attach", $device_code); //机器码
            }
            $unifiedOrder->setParameter("notify_url", "http://" . $_SERVER["HTTP_HOST"] . "/index.php/mobile/WxPay/notify"); //通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型
        } else {
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("sub_openid", $openid);
            $unifiedOrder->setParameter("body", $restaurant_info['restaurant_name']); //商品描述
            $unifiedOrder->setParameter("out_trade_no", $order_sn); //商户订单号
            $unifiedOrder->setParameter("total_fee", $order_info['total_amount'] * 100); //总金额
            if ($device_code) {
                $unifiedOrder->setParameter("attach", $device_code); //机器码
            }
            $unifiedOrder->setParameter("notify_url", "http://" . $_SERVER["HTTP_HOST"] . "/index.php/mobile/WxPay/notify"); //通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型
            //非必填参数，商户可根据实际情况选填
            $unifiedOrder->setParameter("sub_appid", \WxPayConf_pub::$SUB_APPID); //APPID
            $unifiedOrder->setParameter("sub_mch_id", \WxPayConf_pub::$SUB_MCHID); //子商户号
        }
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3： 使用jsapi调起支付============
        $jsApi = new \JsApi_pub();
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        file_put_contents("./" . "pay_config.txt", '支付参数' . json_encode($jsApiParameters) . "||时间" . date("Y-m-d H:i:s") . "\r\n\r\n", FILE_APPEND);
        $this->assign("jsApiParameters", $jsApiParameters);

    }
    // 支付页
    public function pay_old()
    {
        $order_sn                = I("get.order_sn");
        $orderModel              = order();
        $o_condition['order_sn'] = $order_sn;
        $rel                     = $orderModel->where($o_condition)->field("total_amount,order_sn,desk_code,restaurant_id")->find();
        if (empty($rel)) {
            $this->error("订单号错误~");
        }

        $this->assign("order", $rel);

        session("restaurant_id", $rel['restaurant_id']);
        session("desk_code", $rel['desk_code']);
        cookie('restaurant_id', $rel['restaurant_id'], 1296000); //店铺id默认缓存15天

        $this->isRestaurantType();
        file_put_contents(__DIR__ . "/" . "sesssion2.txt", "sesion：" . json_encode($_SESSION) . "，cookie:" . json_encode($_COOKIE) . "||时间" . date("Y-m-d H:i:s") . "\r\n\r\n", FILE_APPEND);

        /*************************微信支付处理***************************/
        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        //使用jsapi接口
        $jsApi = new \JsApi_pub();
        //=========步骤1：网页授权获取用户openid============
        //通过code获得openid
        //        file_put_contents(__DIR__."/"."visit_num.txt",'|订单号:'.$order_sn."||店铺ID：".$restaurant_id."||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
        if (!isset($_GET['code'])) {
//            file_put_contents(__DIR__."/"."visit_num_set.txt",'no_set|订单号:'.$order_sn."||店铺ID：".$restaurant_id."||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
            //触发微信返回code码
            //            $url = $jsApi->createOauthUrlForCode("http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/Index/pay_old/order_sn/".$order_sn);
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/Index/pay_old/order_sn/" . $order_sn);
            Header("Location: $url");
            exit;
        }
        //获取code码，以获取openid
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();
        session('openid', $openid);
        $this->payConfigLoad($order_sn, $openid);
        $restaurant                 = D('Restaurant');
        $condition['restaurant_id'] = $rel['restaurant_id'];
        $result                     = $restaurant->field('tplcolor_id')->find();
        $this->assign("tpl", $result);
        $set                     = D("set");
        $where                   = array("restaurant_id" => session('restaurant_id'), "type" => 6); // type值为6即会员余额
        $open                    = $set->where($where)->find();
        $orderModel              = order();
        $o_condition['order_sn'] = $order_sn;
        $rel                     = $orderModel->where($o_condition)->field("total_amount,order_sn,desk_code,restaurant_id")->find();
        $this->assign("order", $rel);
        $this->assign('open', $open['if_open']);
        $this->display("pay_old");
    }
    //--------------------------------------------------------------------------------
    public function balance()
    {

        $order_sn = I("order_sn");
        $orderModel              = order();
        $o_condition['order_sn'] = $order_sn;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,desk_code,restaurant_id")->find();
        if (empty($rel)) $this->error("订单号错误~");

        session("restaurant_id", $rel['restaurant_id']);
        session("desk_code", $rel['desk_code']);
        cookie('restaurant_id', $rel['restaurant_id'], 1296000); //店铺id默认缓存15天

        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        //使用jsapi接口
        $jsApi = new \JsApi_pub();
        // //=========步骤1：网页授权获取用户openid============

        if (!isset($_GET['code'])) {
            // echo json_encode(['status' => 1,msg => 'success','data' => 'true']);
            $url  = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/Index/balance/order_sn/" . session('order_sn'));
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
            curl_exec($curl);
            curl_close($curl);
            // exit;
            // Header("Location: $url");
            // exit;
        }

        $blc_where['openid'] = session('openid');
        // $this->log($blc_where);
        $vip_model = M("vip");
        $vip_info  = $vip_model->where($blc_where)->field("id,username,remainder,score,total_consume,business_id,openid,restaurant_or_business,restaurant_id")->find();
        if ($vip_info['restaurant_or_business'] == 1) {
            // 代理
            // 首先判断当前会员是不是当前店铺的会员
            // 查出当前店铺所属的代理ID
            $now_business_id = D('restaurant')->where(array('restaurant_id' => session('restaurant_id')))->getField('business_id');
            // 查出当前会员所属的代理ID
            $now_vip_belong_business_id = $vip_info['business_id'];

            if ($now_business_id != $now_vip_belong_business_id) {
                $content["type"]     = "balance";
                $content["code"]     = "0";
                $content["order_sn"] = $order_sn;
                $content["msg"]      = "您不是当前店铺的会员";
                $content['message']  = '11';
                exit(json_encode($content));
            }
        } else {
            // 店铺
            if ($vip_info['restaurant_id'] != session('restaurant_id')) {
                $content["type"]     = "balance";
                $content["code"]     = "0";
                $content["order_sn"] = $order_sn;
                $content["msg"]      = "您不是当前店铺的会员";
                $content['message']  = '3333';
                exit(json_encode($content));
            }
        }

        $remainder     = $vip_info['remainder']; // 会员原有余额
        $score         = $vip_info['score']; // 会员原有积分
        $total_consume = $vip_info['total_consume']; // 会员原有消费总额
        $vip_id        = $vip_info['id']; // 会员id

        $blc_order_where['order_sn'] = $order_sn;
        $order_model                 = order();
        $blc_order_info              = $order_model->where($blc_order_where)->find();

        if ($blc_order_info['order_status'] >= 3) {
            //余额支付返回content
            $content["type"]     = "balance";
            $content["code"]     = "0";
            $content["order_sn"] = $order_sn;
            $content["msg"]      = "已经支付过了";
            //$this->redirect('Index/index', 'restaurant_id='.session('restaurant_id').'/desk_code/='.session('desk_code'));
            $this->redirect('Index/index', array("restaurant_id" => session('restaurant_id'), 'desk_code' => session('desk_code')), 2, "已经支付过了");
            exit(json_encode($content));
        }
        if ($blc_order_info['total_amount'] > $remainder) {
            //余额支付返回content
            $content["type"]     = "balance";
            $content["code"]     = "0";
            $content["order_sn"] = $order_sn;
            $content["msg"]      = "余额不足，请用其他方式支付";
            exit(json_encode($content));
        } else {
            $order_model->startTrans();

            // 换取积分开始      根据订单号查询店铺id
            $o_where['order_sn'] = $order_sn;
            $restaurant_id       = session("restaurant_id");

            $o_condition['restaurant_id'] = $restaurant_id;
            $o_condition['type']          = 2;
            $if_open                      = M("set")->where($o_condition)->getField("if_open");

            // 判断有没有开启
            if ($if_open) {
                if ($vip_info['restaurant_or_business'] == 1) {

                    $rule_condition['business_id'] = $now_business_id; // 代理
                } else {
                    $rule_condition['restaurant_id'] = session('restaurant_id'); // 店铺
                }

                // 根据代理id查出积分设置规则

                $rule_condition['type'] = 2;
                $rule                   = M("all_benefit")->where($rule_condition)->find();
                if ($rule) {
                    // 判断消费额是否大于等于积分设置的金额
                    if ($blc_order_info['total_amount'] >= $rule['account']) {
                        $get_score = floor($blc_order_info['total_amount'] / $rule['account']) * $rule['benefit'];
                    } else {
                        $get_score = 0;
                    }
                } else {
                    $get_score = 0;
                }
            } else {
                $get_score = 0;
            }
            $blc_vip_data['score']         = $score + $get_score; // 更新会员积分
            $blc_vip_data['total_consume'] = $total_consume + $blc_order_info['total_amount'];
            $blc_order_data["score"]       = $get_score;

            $blc_order_data["order_status"]  = 3;
            $blc_order_data["pay_time"]      = time();
            $blc_order_data["pay_type"]      = 4;
            $blc_order_data["vip_id"]        = $vip_id;
            $blc_order_data["remainder"]     = $remainder - $blc_order_info['total_amount']; // 此时的会员余额
            $blc_order_data["summary_score"] = $score + $get_score; // 此时的会员总分
            $blc_order_data['openid']        = session('openid');
            $blc_order_data['related_user']  = session('openid');
            $order_save_rel                  = $order_model->where($blc_order_where)->save($blc_order_data);

            $blc_vip_data['remainder'] = $remainder - $blc_order_info['total_amount'];
            $save_vip_rel              = $vip_model->where($blc_where)->save($blc_vip_data);

            if ($order_save_rel !== false && $save_vip_rel !== false) {
                $order_status = $order_model->where($blc_order_where)->getField('order_status');
                if ($order_status == 3) {
//
                    // 推送的数据
                    $push_data['type']     = 'weixin_place_order'; // 类型为：下单
                    $push_data['order_sn'] = $order_sn;
                    $push_data['platform'] = 'mobile';
                    $data                  = json_encode($push_data);
                    // 查出当前订单号所属店铺
                    $restaurant_id = order()->where(array('order_sn' => $order_sn))->getField('restaurant_id');
                    $devices_ids   = D('push_to_device_by_ali')->where(array('restaurant_id' => session('restaurant_id')))->field('device_id')->select();
                    $php_title     = 'founpad_restaurant_push'; // 标题
                    /**
                     * 阿里推送公共方法
                     * @param Array $devices_ids 设备ID数组（二维数组）
                     * @param String $php_title 消息标题
                     * @param String $php_body  具体内容
                     * @return mixed|\SimpleXMLElement
                     */
                    $Res                = $this->ali_push_to_android_can_set($devices_ids, $php_title, $data);
                    $datas['messageId'] = $Res['MessageId'];
                    $datas['appKey']    = $Res['appKey'];
                    $datas['order_sn']  = $push_data['order_sn'];
                    $datas['status']    = 0;
                    $datas['save_time'] = time();
                    $datas['push_data'] = $data;
                    $datas['php_title'] = $php_title;
//                    $r                  = handlerPush($datas);

                    //查询推送模式然后进行推送
                    $push      = new ServicePush();
                    $push_type = $push->pushType();
                    if ($push_type == 2) {
                        //核销屏的推送模式
                        $push->pushOneScreen($order_sn);
                    } elseif ($push_type == 3) {
                        //取餐柜的推送模式
                        $push->pushOneCupboard($order_sn);
                    } else {
                        //普通模式不用推
                    }
                    $order_model->commit();
                    $content["code"]          = "1";
                    $content["msg"]           = "支付成功";
                    $content["restaurant_id"] = $restaurant_id;
//                    $this->redirect('/mobile/Index/orderVipSuccess');//支付成功直接跳转 $_SERVER['SERVER_NAME'] http://yunniutest.cloudabull.com/index.php/mobile/Index/orderVipSuccess
                    header("Location: /mobile/Index/orderVipSuccess");
                } else {
                    $order_model->rollback();
                    $content["type"]     = "balance";
                    $content["code"]     = "0";
                    $content["order_sn"] = $order_sn;
                    $content["msg"]      = "支付失败";
                    exit(json_encode($content));
                }
            }

        }
    }

    public function test()
    {
        // 推送的数据
        $push_data['type']     = 'weixin_place_order'; // 类型为：下单
        $push_data['order_sn'] = "CD88aa6666aa8820180907114144";
        $push_data['platform'] = 'mobile';
        $data                  = json_encode($push_data);
        // 查出当前订单号所属店铺
        $restaurant_id = order()->where(array('order_sn' => $push_data['order_sn']))->getField('restaurant_id');
        $devices_ids   = D('push_to_device_by_ali')->where(array('restaurant_id' => $restaurant_id))->field('device_id')->select();
        $php_title     = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        $Res                = $this->ali_push_to_android_can_set($devices_ids, $php_title, $data);
        $datas['messageId'] = $Res['MessageId'];
        $datas['appKey']    = $Res['appKey'];
        $datas['order_sn']  = $push_data['order_sn'];
        $datas['status']    = 0;
        $datas['save_time'] = time();
        $datas['push_data'] = $data;
        $datas['php_title'] = $php_title;
        $r                  = handlerPush($datas);
        dump($datas);
        $sql = M('push_check')->getLastSql();
        dump($Res);
    }
    //--------------------------------------------------------------------------------

    public function getIP() /*获取客户端IP*/
    {
        if (@$_SERVER["HTTP_X_FORWARDED_FOR"]) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (@$_SERVER["HTTP_CLIENT_IP"]) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else if (@$_SERVER["REMOTE_ADDR"]) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } else if (@getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (@getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } else if (@getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            $ip = "Unknown";
        }

        return $ip;
    }

    // 改变堂吃外带类型
    public function change_order_type()
    {
        $order_sn   = I('post.order_sn');
        $order_type = I('post.order_type');
        $res        = order()->where(array('order_sn' => $order_sn))->save(array('order_type' => $order_type));
        if ($res !== false) {
            $return['code'] = 1;
            $return['msg']  = '成功';
            exit(json_encode($return));
        } else {
            $return['code'] = 0;
            $return['msg']  = '成功';
            exit(json_encode($return));
        }
    }
    public function demo()
    {
        $restaurant_id = '131';
        session("restaurant_id", $restaurant_id);
        cookie('restaurant_id', $restaurant_id, 1296000); //店铺id默认缓存15天
        $this->isRestaurantType();
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        //使用jsapi接口
        $jsApi = new \JsApi_pub();
        if (!isset($_GET['code'])) {
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/Index/demo");
            Header("Location: $url");
            exit;
        }
        //获取code码，以获取openid
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $data         = $jsApi->getaccess_token();
        $openid       = $data['openid'];
        $access_token = $data['access_token'];
        $url2         = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
        $res2         = http_get($url2);
        $res3         = json_decode($res2, true);
        print_r($res3);
    }

    public function pay_old_user()
    {
        $order_sn                = I("get.order_sn");
        $orderModel              = order();
        $o_condition['order_sn'] = $order_sn;
        $rel                     = $orderModel->where($o_condition)->field("total_amount,order_sn,desk_code,restaurant_id")->find();
        if (empty($rel)) {
            $this->error("订单号错误~");
        }

        $this->assign("order", $rel);

        session("restaurant_id", $rel['restaurant_id']);
        session("desk_code", $rel['desk_code']);
        cookie('restaurant_id', $rel['restaurant_id'], 1296000); //店铺id默认缓存15天

        $this->isRestaurantType();
        file_put_contents(__DIR__ . "/" . "sesssion2.txt", "sesion：" . json_encode($_SESSION) . "，cookie:" . json_encode($_COOKIE) . "||时间" . date("Y-m-d H:i:s") . "\r\n\r\n", FILE_APPEND);

        /*************************微信支付处理***************************/
        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        //使用jsapi接口
        $jsApi = new \JsApi_pub();

        if (!isset($_GET['code'])) {
            //触发微信返回code码
            //            $url = $jsApi->createOauthUrlForCode("http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/Index/pay_old/order_sn/".$order_sn);
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME") . "/index.php/mobile/Index/pay_old_user/order_sn/" . $order_sn);
            Header("Location: $url");
            exit;
        }
        //获取code码，以获取openid
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenid();
        session('openid', $openid);
        $set   = D("set");
        $where = array("restaurant_id" => session('restaurant_id'), "type" => 6); // type值为6即会员余额
        $open  = $set->where($where)->find();
        $this->payConfigLoad($order_sn, $openid);
        $restaurant                 = D('Restaurant');
        $condition['restaurant_id'] = $rel['restaurant_id'];
        $result                     = $restaurant->field('tplcolor_id')->find();
        $this->assign("tpl", $result);
        $info = M('restaurant')->where($condition)->field('restaurant_name,logo,address,business_id')->find();
        $this->assign('info', $info);
        $orderModel              = order();
        $o_condition['order_sn'] = $order_sn;
        $rel                     = $orderModel->where($o_condition)->field("total_amount,order_sn,desk_code,restaurant_id")->find();
        $this->assign("order", $rel);
        $this->assign('open', $open['if_open']);
        $this->display('pay_old_user');

    }

    /**
     * 阿里推送公共方法（能够进行推送时间等的控制）
     * @param Array $devices_ids 设备ID数组
     * @param String $php_title 消息标题
     * @param String $php_body  具体内容
     * @return mixed|\SimpleXMLElement
     */
    public function ali_push_to_android_can_set($devices_ids, $php_title, $php_body)
    {
        // 设置你自己的AccessKeyId/AccessSecret/AppKey
        $ali_push_config = D('jubaopen_ali_push_config')->find();
        $accessKeyId     = $ali_push_config['accessKeyId'];
        $accessKeySecret = $ali_push_config['accessKeySecret'];
        $appKey          = $ali_push_config['appKey'];

        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $accessKeyId, $accessKeySecret);
        $client         = new \DefaultAcsClient($iClientProfile);
        $request        = new Push\PushRequest();
        // 推送目标
        $request->setAppKey($appKey);
        $request->setTarget("DEVICE"); //推送目标: DEVICE:推送给设备; ACCOUNT:推送给指定帐号,TAG:推送给自定义标签; ALL: 推送给全部

        // 设备ID数组
        $devices_str = ''; //多台设备用逗号隔开
        foreach ($devices_ids as $key => $val) {
            if ($key == count($devices_ids) - 1) {
                $devices_str .= $val['device_id'];
            } else {
                $devices_str .= $val['device_id'] . ',';
            }
        }
        $request->setTargetValue($devices_str); //根据Target来设定，如Target=DEVICE, 则对应的值为 设备id1,设备id2. 多个值使用逗号分隔.(帐号与设备有一次最多100个的限制)

        $request->setDeviceType("ANDROID"); //设备类型 ANDROID iOS ALL.
        $request->setPushType("MESSAGE"); //消息类型 MESSAGE NOTICE
        $request->setTitle($php_title); // 消息的标题
        $request->setBody($php_body); // 消息的内容
        // 推送控制
        $expireTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+300 second')); //设置失效时间为5分钟
        $request->setExpireTime($expireTime);
        $request->setStoreOffline("true"); // 离线消息是否保存,若保存, 在推送时候，用户即使不在线，下一次上线则会收到

        $response         = $client->getAcsResponse($request);
        $arr['MessageId'] = $response->MessageId;
        $arr['RequestId'] = $response->RequestId;
        $arr['appKey']    = $appKey;
        return $arr;
    }

    public function orderVipSuccess()
    {
        $this->assign('restaurant_id', session('restaurant_id'));
        $this->display('orderVipSuccess');
    }

    /**
     * 记录日志
     * @param string $msg
     */
    public function log($msg)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        echo "{$msg} \n";
        file_put_contents(__DIR__ . "/log/" . date('Y-m-d') . '.log', date('Y-m-d H:i:s') . ':' . $msg . "\n", FILE_APPEND);
    }
}
