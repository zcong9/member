<?php
/**
 * 会员控制器
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/10
 * Time: 10:04
 */
namespace Admin\Controller;
use Admin\Model\MembersModel;
use Admin\Model\PrepaidOrderModel;
use Admin\Model\AllBenefitModel;
use Admin\Model\BusinessSetModel;
class MemberlistController extends BaseController
{
    //获取会员
    public function vip()
    {
        $p = I("param.page",1,'int');
        $vipModel = new MembersModel();
        $param['merchant_id'] = $this->merchant_id;
        $param['name_key'] = I('get.name_key');
        $lists = $vipModel->getVipList($param, '',$p);
//        dump($lists['Arrlist']);
        $this->assign('lists', $lists['Arrlist']);
        $this->assign('page',$lists['show']);
        $this->display();
    }

    public function vip_info(){
        $id = I("param.id",0,'int');
        if(empty($id)){
            exit(json_encode([]));
        }
        $vipModel = new MembersModel();
        $info = $vipModel->getVipInfo(['id'=>$id]);
        $info['money'] = $info['money'] /100;
        exit(json_encode($info));
    }
    public function vip_post(){
        $data = I('post.');
        $id = $data['id'];
        if(!empty($data['password']) && !empty(!empty($data['passwords']))){
            if($data['password'] != $data['passwords']){
                exit(json_encode(['code'=>0, 'msg'=>'两次密码不一致']));
            }
            $data['pay_pwd'] = md5($data['password']);
        }
        unset($data['password']);
        unset($data['passwords']);
        // if(!empty($data['add_remainder']) && preg_match('/^(\-?)[1-9]d*|0$/', $data['add_remainder'])){
        if(!empty($data['add_remainder']) && preg_match('/^(\-?)\d{1,9}$/', $data['add_remainder'])){
            $type = $data['add_remainder'] > 0 ? 6: 7;
            $type_msg = $data['add_remainder'] > 0 ? '后台手动调整增加余额': '后台手动调整减少余额';
            set_vip_remainder($id, $data['add_remainder'], $type, '', '', $type_msg);
        }
        unset($data['add_remainder']);
 
        $vipModel = new MembersModel();
        $info = $vipModel->updateVipInfoById(['id'=>$id], $data);
        exit(json_encode(['code'=>1, 'msg'=>'编辑成功']));
    }
    // 删除会员
    public function delInfo(){
        $id = I('post.id');
        $vipModel = new MembersModel();
        $info = $vipModel->updateVipInfoById(['id'=>$id], ['is_del'=>1]);
        exit(json_encode(['code'=>1, 'msg'=>'编辑成功']));
    }
    /**
    * 充值订单
     */
    public function prepaidOrder(){
        $p = I("param.page",1,'int');
        $param['keyword'] = I('get.keyword');
        $prepaidOrder = new PrepaidOrderModel();
        $param['merchant_id'] = $this->merchant_id;
        $lists = $prepaidOrder->getPrepaidOrderList($param, 'o.*, v.nickname', $p);

        $this->assign('lists', $lists['Arrlist']);
        $this->assign('page',$lists['show']);
        $this->display();
    }
    /**
    * 充值设置
     */
    public function prepaid(){
        $BusinessSet            = new BusinessSetModel();
        $param['merchant_id'] = $this->merchant_id;
        $param['type']          = 0;      // 类型为0时预充值
        $setInfo = $BusinessSet->getBusinessSetInfo($param);
        $this->assign('setInfo', $setInfo);

        $AllBenefit           = new AllBenefitModel();
        $param['type']          = 1;
        $lists = $AllBenefit->getPrepaidList($param);
        $this->assign('lists', $lists);
        $this->display();
    }
    // 添加
    public function add_prepaid(){
        $data = I('post.');
        if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $data['account'])) {
            $this->error("充值金额格式错误");
        }
        if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $data['benefit'])){
            $this->error("赠送金额格式错误");
        }
        $data['merchant_id'] = $this->merchant_id;
        $data['type']        = 1;
        $AllBenefit           = new AllBenefitModel();
        //根据充值金额判断是否已存在该条规则
        $result = $AllBenefit->checkPrepaid($data);
        if(!$result){
            $result = $AllBenefit->addPrepaid($data);
            if($result){
                $this->get_prepaid();
            }else{
                $this->error("添加失败，请重试");
            }
        }else{
            $this->error("已存在相同的预充值信息，请勿重复添加");
        }
    }
    // 编辑
     public function save_prepaid(){
         $account = I('post.account');
         if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $account)) {
             $this->error("充值金额格式错误");
         }
         $benefit = I('post.benefit');
         if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $benefit)){
             $this->error("赠送金额格式错误");
         }
         $id = I('post.id');
         $data['merchant_id'] = $this->merchant_id;
         $data['account']       = $account;
         $data['benefit']       = $benefit;
         $data['type']          = 1;
         $AllBenefit            = new AllBenefitModel();
         //根据充值金额判断是否已存在该条规则
         $result = $AllBenefit->checkPrepaid($data);
         if(!$result){
             unset($data['merchant_id']);
             unset($data['type']);
             $result = $AllBenefit->updatePrepaidById(['id'=>$id], $data);
             if($result){
                 $this->get_prepaid();
             }else{
                 $this->error("编辑失败，请重试");
             }
         }else{
             $this->error("已存在相同的预充值信息，请勿重复添加");
         }
     }
     // 删除
    public function del_prepaid(){
        $id = I('post.id');
        $AllBenefit            = new AllBenefitModel();
        $res = $AllBenefit->delPrepaidById(['id'=>$id]);
        if($res){
            $this->get_prepaid();
        }else{
            $this->error("删除失败，请重试");
        }
    }
    # 获取预充值信息
    public function get_prepaid()
    {
        $condition['merchant_id'] = $this->merchant_id;
        $condition['type']        = 1;
        $AllBenefit           = new AllBenefitModel();
        $prepaid_rules            = $AllBenefit->getPrepaidList($condition);
        $this->assign("prepaid_rules", $prepaid_rules);
        $this->display("ajaxPrepaid");
    }
    public function prepaid_onoff(){
        # 接收设置信息，存入设置表
        $if_open = trim(I("post.if_open")); // 是否开启  1开启，0关闭
        // 预充值开关
        $param['merchant_id']   = $this->merchant_id;
        $param['type']          = 0;      // 类型为0时预充值
        $data['merchant_id']    = $this->merchant_id;
        $data['if_open']        = $if_open;
        $data['type']           = 0;
        $BusinessSet            = new BusinessSetModel();
        $res = $BusinessSet->updatePrepaidById($param, $data);
        if($res){
            $this->success("更新成功");
        }else{
            $this->error("更新失败");
        }
    }

    # 公众号设置
    public function weixinAccounts()
    {
        $condition['merchant_id'] = $this->merchant_id;
        $public_number_set_model = D("public_number_set");
        $public_number_set = $public_number_set_model->where($condition)->find();

        $url = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/Member/Auth/index?mchId='.$this->merchant_id;

        $this->assign('url',$url);
        $this->assign("public_number_set", $public_number_set);
        $this->assign("merchant_id", $this->merchant_id);

        $this->display();
    }

    public function add_public_number_set()
    {
        $public_number_set = D("public_number_set");
        // 根据ID来判断是添加还是编辑
        if (I("post.id")) {
            // 编辑
            if ($public_number_set->create()) {
                if ($public_number_set->save() !== false) {
                    $this->success("编辑成功");
                    // $this->get_public_number_set();
                } else {
                    $this->error("编辑失败");
                }
            } else {
                $this->error("编辑失败");
            }
        } else {
            // 添加
            if ($public_number_set->create()) {
                if ($public_number_set->add()) {
                    $this->success("添加成功");
                    // $this->get_public_number_set();
                } else {
                    $this->error("添加失败");
                }
            } else {
                $this->error("添加失败");
            }
        }

    }

    # 页面加载完自动加载公众号设置信息
    public function get_public_number_set()
    {
        $condition['merchant_id'] = $this->merchant_id;
        $public_number_set_model = D("public_number_set");
        $public_number_set = $public_number_set_model->where($condition)->find();
        $this->assign("public_number_set", $public_number_set);
        $this->assign("merchant_id", $this->merchant_id);
        $this->display("ajaxPublicNumberSet");
    }

    //公众号上传文件
    public function txt()
    {
        if (IS_POST) {
            //获取文件名
            $fileName = $_FILES['file']['name'];
            $tmp_name = $_FILES['file']['tmp_name'];
            $file = $_SERVER['DOCUMENT_ROOT'] . '/' . $fileName;
            $type = strstr($fileName, "."); //获取从"."到最后的字符
            if ($type != ".txt") {
                $this->error("对不起,您上传文件的格式不正确!!", U('weixinAccounts'));
            } else {
                $status = move_uploaded_file($tmp_name, $file);
                if ($status) {
                    $this->success('提交成功', U('weixinAccounts'));
                } else {
                    $this->error("提交失败", U('weixinAccounts'));
                }
            }

        }
    }
    // 导出
    public function memberExcel(){
        $vipModel = new MembersModel();
        $param['merchant_id'] = $this->merchant_id;
        $lists = $vipModel->getVipAll($param, '');
        foreach($lists as $k=>$v){
            $lists[$k]['sex'] = $v['sex'] == 1 ? '男': '女';
            $lists[$k]['status'] = $v['status'] == 1 ? '正常': '关闭';
            $lists[$k]['create_at'] = date('Y-m-d H:i:s', $v['create_at']);
            $lists[$k]['money'] = !empty($v['money']) ? $v['money']/100 : 0;
        }
        $xlsName = "会员信息表、导出时间(" . date("Y-m-d", time()) . ").xls";
        $xlsCell = array(
            array('id', '会员ID'),
            array('nickname', '微信昵称'),
            array('sex', '性别'),
            array('realname', '姓名'),
            array('mobile', '电话'),
            array('status', '状态'),
            array('money', '余额（元）'),
            array('addr', '地址'),
            array('create_at', '添加时间'),
            array('', '增加余额（元）'),
        );
        exportExcel($xlsName, $xlsCell, $lists);
    }

    // 导入
    public function memberImportPost(){
        $upload           = new \Think\Upload(); // 实例化上传类
        // $upload->maxSize  = 3145728; // 设置附件上传大小
        $upload->exts     = array('xls'); // 设置附件上传类型
        #如果碰上无法上传，出现根目录找不到的情况，可能是文件夹权限导致
        $upload->savePath = '/Public/memberExcel/';
        $upload->rootPath = './'; // 设置附件上传根目录  必须设置

        #上传单个文件
        $info = $upload->uploadOne($_FILES['file1']);
        if (!$info) {
        # 上传错误提示错误信息
           $this->error($upload->getError());
        }
        $Absolute_Path=str_replace("/index.php","",$_SERVER['SCRIPT_FILENAME']);
        // 上传成功 获取上传文件信息
        $memberflie          = $Absolute_Path . $info['savepath'] . $info['savename'];
        // $memberflie = $Absolute_Path.'/Public/memberExcel/2021-06-30/60dc2f5ab29ef.xls';
        if (!is_file($memberflie)) {
            $this->error('文件不存在');
        }
        vendor("PHPExcel.PHPExcel");

        import("Org.Util.PHPExcel");
        $PHPExcel = new \PHPExcel();
        
        $extension = strtolower( pathinfo($memberflie, PATHINFO_EXTENSION));
        if($extension =='xlsx'){
            $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
        }else if($extension =='xls'){
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        }
        $obj_PHPExcel =$objReader->load($memberflie, $encode = 'utf-8');  //加载文件内容,编码utf-8
        
        $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
        array_shift($excel_array);  //删除第一个数组(标题);
    
        $i=0;
        $vipModel = new MembersModel();

        foreach($excel_array as $k=>$v) {
            $data = [];
            $data['id'] = $v[0];
            $data['add_money'] = $v[9];
            $info = $vipModel->getVipInfo(['id'=>$data['id']], 'id');
            if($info && !empty($data['add_money']) && preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $data['add_money'])){
                $data['add_money'] = bcmul($data['add_money'], 100);
                set_vip_remainder($info['id'], $data['add_money'], 6, '', '', '批量-后台手动充值');
            }else{
                \Think\Log::write('后台修改会员余额失败：'.json_encode($v,JSON_UNESCAPED_UNICODE));
            }
        }
        $this->success("导入完成");
    }
}
