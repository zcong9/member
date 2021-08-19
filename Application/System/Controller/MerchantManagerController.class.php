<?php
namespace System\Controller;

use System\Model\MerchantModel;
use System\Model\MerchantManagerModel;
class MerchantManagerController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        //$this->checkLogin();
    }

	public function index()
    {
        $managerDB = new MerchantManagerModel();
        $param = [];
        if (IS_AJAX) {
            $param = I('param.');
            $data = $managerDB->getList($param, 'c.*, m.merchant_name, r.role_name');
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
        $data = $managerDB->getList($param,'c.*, m.merchant_name');
        $this->assign('page', $data['show']);
        $this->assign('lists', $data['Arrlist']);
        return $this->display();
    }
	public function edit(){
        $id = I('id/d', 0);
        $managerModel = new MerchantManagerModel();
        if (IS_POST) {
            $param = I('post.');
            if(!$managerModel->create($param)){
                $this->error($managerModel->getError());
            }
            if(empty($param['pwd'])){
                unset($param['pwd']);
            }else{
                $param['pwd'] = md5($param['pwd']);
            }
            unset($param['pwd_word']);
            unset($param['account']);
            $res = $managerModel->save($param);
            if($res){
                $this->success('编辑成功');
            }
            $this->error('编辑失败');
            exit;
        }
        
        $info = $managerModel->getInfo($id);
        $this->assign('data', $info);
        $merchant = D('merchant')->field('merchant_id, merchant_name')->where(['status'=>1])->select();
        $this->assign('merchant', $merchant);
        $rolelist = D('role')->field('id, role_name')->select();
        $this->assign('rolelist', $rolelist);
        $this->display();
    }
	
	public function add()
    {
        if (IS_POST) {
            $param = I('post.');
            $merchantManager = new MerchantManagerModel();
            if(!$merchantManager->create($param)){
                $this->error($merchantManager->getError());
            }
            if(empty($param['merchant_id'])){
                $this->error('请选择商户');
            }
            // 登录账号信息
            $managerArr['merchant_id']  = $param['merchant_id'];
            $managerArr['account']      = $param['account'];
            $managerArr['real_name']    = $param['real_name'];
            $managerArr['pwd']          = md5($param['pwd']);
            $managerArr['role_id']      = $param['role_id'];
            $managerArr['status']       = $param['status'];
            $managerArr['create_at']    = time();
            $info = $merchantManager->where(['account'=>$param['account']])->find();
            if($info){
                $this->error('管理员账号已存在，请重新输入');
            }
            try {
                $res = $merchantManager->add($managerArr);
                $this->success('添加成功');
            } catch (\Exception $e) {
                $this->error('添加失败');
            }
        }
        $merchant = D('merchant')->field('merchant_id, merchant_name')->where(['status'=>1])->select();
        $this->assign('merchant', $merchant);
        $rolelist = D('role')->field('id, role_name')->select();
        $this->assign('rolelist', $rolelist);
        $this->display('edit');
    }

    public function editStatus(){
        $id = I('post.id');
        $status = I('post.status');
        $managerModel = new MerchantManagerModel();
        try{
            $condition['id'] = $id;
            $data['status'] = $status;
            $managerModel->where($condition)->save($data);
            $this->success('更新成功');
        }catch(\Exception $e){
            $this->error('更新失败');
        }
    }
}



