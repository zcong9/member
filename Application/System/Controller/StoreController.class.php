<?php
namespace System\Controller;

use System\Model\MerchantModel;
use System\Model\StoreModel;
class StoreController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        //$this->checkLogin();
    }

	public function index()
    {
        $storeModel = new StoreModel();
        $param = [];
        if (IS_AJAX) {
            $param = I('param.');
            $data = $storeModel->getList($param, 's.*,m.merchant_name');
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
        $data = $storeModel->getList($param, 's.*,m.merchant_name');
        $this->assign('page', $data['show']);
        $this->assign('lists', $data['Arrlist']);
        return $this->display();
    }
	public function edit(){
        $id = I('id/d', 0);
        $storeModel = new StoreModel();
        if (IS_POST) {
            $param = I('post.');
            
            if(!$storeModel->create($param)){
                $this->error($storeModel->getError());
            }
            $res = $storeModel->save($param);
            if($res){
                $this->success('编辑成功');
            }
            $this->error('编辑失败');
            exit;
        }
    
        $info = $storeModel->getInfo($id);
        $info['merchant_name'] = D('merchant')->where(['merchant_id'=>$info['merchant_id']])->getfield('merchant_name');
        $this->assign('data', $info);
        

        $this->display();
    }
	
	public function add()
    {
        if (IS_POST) {
            $param = I('post.');
            $merchantModel = new MerchantModel();
            if(!$merchantModel->create($param)){
                $this->error($merchantModel->getError());
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
            $merchantModel->startTrans();
            try {
                $res = $merchantModel->add($merchantArr);
                $managerArr['merchant_id'] = $merchantModel->getLastInsID(); // 获取ID
                D('merchant_manager')->add($managerArr);
                $merchantModel->commit();
                $this->success('添加成功');
            } catch (\Exception $e) {
                $merchantModel->rollback();
                $this->error('添加失败');
            }
        }
        $merchant = D('merchant')->field('merchant_id, merchant_name')->where(['status'=>1])->select();
        $this->assign('merchant', $merchant);
        $this->display('edit');
    }

    public function editStatus(){
        $merchant_id = I('post.merchant_id');
        $status = I('post.status');
        $storeModel = new StoreModel();
        try{
            $condition['store_id'] = $merchant_id;
            $data['status'] = $status;
            $storeModel->where($condition)->save($data);
            $this->success('更新成功');

        }catch(\Exception $e){            
            $this->error('更新失败');
        }
    }
}



