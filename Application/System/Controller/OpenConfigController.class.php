<?php
namespace System\Controller;

use System\Model\MerchantModel;
use System\Model\AuthConfigModel;
class OpenConfigController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        //$this->checkLogin();
    }

	public function index()
    {
        $authconfigDB = new AuthConfigModel();
        $param = [];
        if (IS_AJAX) {
            $param = I('param.');
            $data = $authconfigDB->getList($param, 'c.*, m.merchant_name');
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
        $data = $authconfigDB->getList($param,'c.*, m.merchant_name');

        $this->assign('page', $data['show']);
        $this->assign('lists', $data['Arrlist']);
        return $this->display();
    }
	public function edit(){
        $id = I('id/d', 0);
        $authconfigDB = new AuthConfigModel();
        if (IS_POST) {
            $param = I('post.');
            if(!$authconfigDB->create($param)){
                $this->error($authconfigDB->getError());
            }
        
            $res = $authconfigDB->save($param);
            if($res !== false){
                $this->success('编辑成功');
            }
            $this->error('编辑失败');
            exit;
        }
        
        $info = $authconfigDB->getInfo($id);
        $this->assign('data', $info);
        $merchant = D('merchant')->field('merchant_id, merchant_name')->where(['status'=>1])->select();
        $this->assign('merchant', $merchant);
        $this->display();
    }
	
	public function add()
    {
        if (IS_POST) {
            $param = I('post.');
            $authconfigDB = new AuthConfigModel();
            if(!$authconfigDB->create($param)){
                $this->error($authconfigDB->getError());
            }
            if(empty($param['merchant_id'])){
                $this->error('请选择商户');
            }
            // 登录账号信息
            $managerArr['merchant_id']  = $param['merchant_id'];
            $managerArr['appid']      = $param['appid'];
            $managerArr['secret']    = $param['secret'];
            $managerArr['platform'] = 1;
            $managerArr['create_at']    = time();
            $appid = $authconfigDB->where(['appid'=>$param['appid']])->find();
            if($appid){
                $this->error('appid已存在，请重新输入');
            }
            $secret = $authconfigDB->where(['secret'=>$param['secret']])->find();
            if($secret){
                $this->error('secret已存在，请重新输入');
            }
            try {
                $res = $authconfigDB->add($managerArr);
                $this->success('添加成功');
            } catch (\Exception $e) {
                $this->error('添加失败');
            }
        }
        $merchant = D('merchant')->field('merchant_id, merchant_name')->where(['status'=>1])->select();
        $this->assign('merchant', $merchant);
        $this->display('edit');
    }

    public function delete(){
        $id = I('get.id');
        $authconfigDB = new AuthConfigModel();
        try{
            $condition['id'] = $id;
            $authconfigDB->where($condition)->delete();
            $this->success('更新成功');
        }catch(\Exception $e){
            $this->error('更新失败');
        }
    }
    public function editStatus(){
        $id = I('post.id');
        $status = I('post.status');
        $authconfigDB = new AuthConfigModel();
        try{
            $condition['id'] = $id;
            $data['status'] = $status;
            $authconfigDB->where($condition)->save($data);
            $this->success('更新成功');
        }catch(\Exception $e){
            $this->error('更新失败');
        }
    }
}



