<?php
namespace System\Controller;

use System\Model\MerchantModel;
use System\Model\RoleModel;
class RoleController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        //$this->checkLogin();
    }

	public function index()
    {
        $roleModelModel = new RoleModel();
        $param = [];
        if (IS_AJAX) {
            $param = I('param.');
            $data = $roleModelModel->getList($param);
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
        $data = $roleModelModel->getList($param);
        $this->assign('page', $data['show']);
        $this->assign('lists', $data['Arrlist']);
        return $this->display();
    }
	public function edit(){
        $id = I('id/d', 0);
        $roleModel = new RoleModel();
        if (IS_POST) {
            $param = I('post.');
            if(!$roleModel->create($param)){
                $this->error($roleModel->getError());
            }
            $condition['role_name']     =   $param['role_name'];
            $condition['id']     =   array('NEQ',$param['id']);
                # 除了该id的记录，查看该代理下除了本条记录，是否还设置了相同角色名字的角色
            $roleRes    =  $roleModel->field('role_name')->where($condition)->find();
            if($roleRes){
                $this->error("操作失败，该角色名称已存在！");
            }

            $managerArr['role_name']    = $param['role_name'];
            $managerArr['role_desc']    = $param['role_desc'];
            $managerArr['action_list']  =   !empty($param['action_list']) ? implode(',', $param['action_list']): '';
            $res = $roleModel->where(['id'=>$param['id']])->save($managerArr);
            if($res){
                $this->success('编辑成功');
            }
            $this->error('编辑失败');
            exit;
        }
    
        $info = $roleModel->getInfo($id);
        $action_list = explode(',', $info['action_list']);
        # 获取该代理的权限  添加及修改时需要用到
        $actionList   =   M('action')->field('id,action_name')->select();
        foreach($actionList as $k =>$v){
            if(in_array($v['id'], $action_list)){
                $actionList[$k]['checked'] = 'checked=checked';
            }else{
                $actionList[$k]['checked'] = '';
            }
        }
        $this->assign("actionList",$actionList);

        $this->assign('data', $info);
        

        $this->display();
    }
	
	public function add()
    {
        if (IS_POST) {
            $param = I('post.');
            $roleModel = new RoleModel();
            if(!$roleModel->create($param)){
                $this->error($roleModel->getError());
            }
            $condition['role_name']     =   $param['role_name'];
                # 除了该id的记录，查看该代理下除了本条记录，是否还设置了相同角色名字的角色
            $roleRes    =  $roleModel->field('role_name')->where($condition)->find();
            if($roleRes){
                $this->error("操作失败，该角色名称已存在！");
            }

            $managerArr['role_name']    = $param['role_name'];
            $managerArr['role_desc']    = $param['role_desc'];
            $managerArr['action_list']  =   !empty($param['action_list']) ? implode(',', $param['action_list']): '';
            try {
                $res = $roleModel->add($managerArr);
                $this->success('添加成功');
            } catch (\Exception $e) {
                $this->error('添加失败');
            }
        }
        # 获取该代理的权限  添加及修改时需要用到
        $actionList   =   M('action')->field('id,action_name')->select();
        $this->assign("actionList",$actionList);
        $this->display('edit');
    }
}



