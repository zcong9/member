<?php
namespace Admin\Controller;
use Think\Controller;

class BaseController extends Controller
{
    // 需要进行权限控制的方法
    protected $actionRoles = [
        'moveup1',
        'movedown1',
        'createDishetype',
        'modifyDishestype',
        'delDishestype',
        'categoryUpdstate',
        'moveup2',
        'movedown2',
        'modifyfoodinfo',
        'delfoodinfo',
        'updstate',
        'showDisinfoByKey',
    ];

    protected $merchant_id;
    // protected $business_id;
    public function __construct() {
        parent::__construct();
        //$is_appoint_shop = C("BUSINESS_ID") == session('business_id') || session('business_id') == 25?true:false;//指定商家是否支持菜品英文等功能判断
        //$this->assign('is_en', $is_appoint_shop);
        $this->merchant_id = session("merchant_id");
        // $this->business_id = session("business_id");
        // $is_role = M('merchant_manager')->where(['merchant_id' => $this->merchant_id])->getField('role_id');

        // if ($this->merchant_id) {
        //     $change_menu_data_info = M('change_menu_data_time')->where(['merchant_id' => $this->merchant_id])->find();

        //     if (empty($change_menu_data_info)) M('change_menu_data_time')->add(['restaurant_id' => $this->restaurant_id, 'last_change_menu_data_time' => 0]);
        // }

        if ($is_role) {
            // 权限控制
//            if (in_array(ACTION_NAME,$this->actionRoles)) {
//                // 查看是否有相应的操作权限
//                $action_list = M('restaurant_manager')->alias('rm')
//                    ->join('role r on rm.role_id = r.id')
//                    ->where(['rm.restaurant_id' => $this->restaurant_id])
//                    ->getField('action_list');
//
//                // 权限列表
//                $action_id = M('action')->where(['controller' => CONTROLLER_NAME,'action'=>ACTION_NAME])->getField('id');
//                if ( !in_array($action_id,explode(',',$action_list) ) ) {
//                    exit('无权操作该功能,请联系管理员');
//                }
//            }
        }
    }

    public function _empty(){
        redirect("/index.php/Admin/Index/login");
    }

    //返回json 通用方法
    public function returnJson($code, $data) {
        $returnData['code'] = $code;
        $returnData['msg'] = $data;
        echo json_encode($returnData);
        exit;
    }
}