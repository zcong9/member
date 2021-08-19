<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:59
 */

namespace System\Model;

use Think\Model;

class ActionModel extends Model
{
    protected $_validate = array(
        //内置验证require不能为空
        array('action_name','require','权限名不能为空',1),
        array('model','require','模块名不能为空！',1),
        array('controller','require','控制器名不能为空！',1),
        array('action','require','方法名不能为空！',1),
    );

    // 列表
    function getList($param, $filed='*',$pageNum = 10){
        $page = isset($param['page'])? $param['page']: 1;

        $condition = [];
        if(!empty($param['keyword'])){
            $condition['action_name'] = array('like',"%".$param['keyword']."%");
        }
        $total = $this->where($condition)->count();
        $Page = new \Think\PageAjax($total, $pageNum);
        $data['show'] = $Page->show(null);
        $data['Arrlist'] = $this->field($filed)
                            ->where($condition)
                            ->limit($Page->firstRow . ',' . $Page->listRows)
                            ->order('id DESC')
                            ->select();
        return $data;
    }

    //
    function getInfo($id,$filed='*'){
        if($id){
            return $this->where(['id'=>$id])->field($filed)->find();
        }
    }

    //通过ID对会员组进行更新
    function updateInfoById($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }
}