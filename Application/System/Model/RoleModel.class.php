<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:59
 */

namespace System\Model;

use Think\Model;

class RoleModel extends Model
{
    protected $_validate = array(
        //内置验证require不能为空
        array('role_name','require','角色名称不能为空',1),
    );

    // 列表
    function getList($param, $filed='*',$pageNum = 10){
        $page = isset($param['page'])? $param['page']: 1;

        $condition = [];
        if(!empty($param['keyword'])){
            $condition['role_name'] = array('like',"%".$param['keyword']."%");
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