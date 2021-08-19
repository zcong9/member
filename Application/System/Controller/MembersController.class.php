<?php
namespace System\Controller;

use System\Model\MerchantModel;
use System\Model\MembersModel;
class MembersController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        //$this->checkLogin();
    }

	public function index()
    {
        $membersModel = new MembersModel();
        $param = [];
        if (IS_AJAX) {
            $param = I('param.');
            $data = $membersModel->getList($param, 'u.*,m.merchant_name');
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
        $data = $membersModel->getList($param,'u.*,m.merchant_name');
        $this->assign('page', $data['show']);
        $this->assign('lists', $data['Arrlist']);
        return $this->display();
    }
	public function edit(){
        $id = I('id/d', 0);
        // if (IS_POST) {
        //     $param = I('post.');
        //     $membersModel = new MembersModel();
        //     if(!$membersModel->create($param)){
        //         $this->error($membersModel->getError());
        //     }
        //     $res = $membersModel->save($param);
        //     if($res){
        //         $this->success('编辑成功');
        //     }
        //     $this->error('编辑失败');
        //     exit;
        // }
        
        $membersModel = new MembersModel();
        $info = $membersModel->getInfo($id);
        $merchantModel = new MerchantModel();
        $info['merchant_name'] = $merchantModel->where(['merchant_id'=>$info['merchant_id']])->getfield('merchant_name');
        $info['sex'] = $info['sex'] == 1 ? '男':'女';
        $this->assign('data', $info);
        $this->display();
    }
	
	public function add()
    {
        if (IS_POST) {
            $param = I('post.');
            $membersModel = new MembersModel();
            if(!$membersModel->create($param)){
                $this->error($membersModel->getError());
            }
            $merchantManager = new MerchantManagerModel();
            if(!$merchantManager->create($param)){
                $this->error($merchantManager->getError());
            }
            // 商户基础信息
            $merchantArr['merchant_name']   = $param['merchant_name'];
            $merchantArr['alias_name']      = $param['alias_name'];
            $merchantArr['contact_name']    = $param['contact_name'];
            $merchantArr['contact_phone']   = $param['contact_phone'];
            $merchantArr['contact_email']   = $param['contact_email'];
            $merchantArr['address']         = $param['address'];
            $merchantArr['status']          = $param['status'];
            $merchantArr['create_at']       = time();
            // 登录账号信息
            $managerArr['account']      = $param['account'];
            $managerArr['real_name']    = $param['real_name'];
            $managerArr['pwd']          = md5($param['pwd']);
            $managerArr['role_id']      = $param['role_id'];
            $managerArr['status']       = $param['status'];
            $managerArr['create_at']    = time();
            $info = D('merchant_manager')->where(['account'=>$param['account']])->find();
            if($info){
                $this->error('管理员账号已存在，请重新输入');
            }
            $membersModel->startTrans();
            try {
                $res = $membersModel->add($merchantArr);
                $managerArr['merchant_id'] = $membersModel->getLastInsID(); // 获取ID
                D('merchant_manager')->add($managerArr);
                $membersModel->commit();
                $this->success('添加成功');
            } catch (\Exception $e) {
                $membersModel->rollback();
                $this->error('添加失败');
            }
        }
        $this->display('edit');
    }

    public function editStatus(){
        $id = I('post.id');
        $status = I('post.status');
        $membersModel = new MembersModel();
        try{
            $condition['id'] = $id;
            $data['status'] = $status;
            $membersModel->where($condition)->save($data);
            $this->success('更新成功');

        }catch(\Exception $e){
            $this->error('更新失败');
        }
    }
}



