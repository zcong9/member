<?php
namespace System\Controller;
use System\Model\ActionModel;
class ActionController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        //$this->checkLogin();
    }

	public function index()
    {
        $actionModel = new ActionModel();
        $param = [];
        if (IS_AJAX) {
            $param = I('param.');
            $data = $actionModel->getList($param);
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
        $data = $actionModel->getList($param);
        $this->assign('page', $data['show']);
        $this->assign('lists', $data['Arrlist']);
        return $this->display();
    }
	public function edit(){
        $id = I('id/d', 0);
        $actionModel = new ActionModel();
        if (IS_POST) {
            $param = I('post.');
            if(!$actionModel->create($param)){
                $this->error($actionModel->getError());
            }
            $condition['id']         =   array('NEQ',$param['id']);
            $condition['model']      =   $param['model'];
            $condition['controller'] =   $param['controller'];
            $condition['action']     =   $param['action'];
            $ares    =  $actionModel->field('id')->where($condition)->find();
            if($ares){
                $this->error("操作失败，该权限已存在！");
            }
            $managerArr['action_name']  = $param['action_name'];
            $managerArr['model']        = $param['model'];
            $managerArr['controller']   = $param['controller'];
            $managerArr['action']       = $param['action'];
            $managerArr['desc']         = $param['desc'];
            $res = $actionModel->where(['id'=>$param['id']])->save($managerArr);
            if($res){
                $this->success('编辑成功');
            }
            $this->error('编辑失败');
            exit;
        }
    
        $info = $actionModel->getInfo($id);
        $action_list = explode(',', $info['action_list']);
        # 获取该代理的权限  添加及修改时需要用到
        $actionList   =   M('action')->field('id,action_name')->where(['business_id'=>'775'])->select();
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
            $actionModel = new ActionModel();
            if(!$actionModel->create($param)){
                $this->error($actionModel->getError());
            }
            $condition['model']      =   $param['model'];
            $condition['controller'] =   $param['controller'];
            $condition['action']     =   $param['action'];
                # 除了该id的记录，查看该代理下除了本条记录，是否还设置了相同角色名字的角色
            $ares    =  $actionModel->field('id')->where($condition)->find();
            if($ares){
                $this->error("操作失败，该权限已存在！");
            }
            $managerArr['action_name']  = $param['action_name'];
            $managerArr['model']        = $param['model'];
            $managerArr['controller']   = $param['controller'];
            $managerArr['action']       = $param['action'];
            $managerArr['desc']         = $param['desc'];
            try {
                $res = $actionModel->add($managerArr);
                $this->success('添加成功');
            } catch (\Exception $e) {
                $this->error('添加失败');
            }
        }
        $this->display('edit');
    }
    // 删除
    public function delete(){
        $id = I('post.id');
        $actionModel = new ActionModel();
        try {
            $res = $actionModel->where(['id'=>$id[0]])->delete();
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
    }
    // 批量删除
    public function delAll(){
        $idarr = I('post.id');
        $ids = implode(',', $idarr);
        $actionModel = new ActionModel();
        try {
            $res = $actionModel->where(['id'=>['in', $ids]])->delete();
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
    }
}



