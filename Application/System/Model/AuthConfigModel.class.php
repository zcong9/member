<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:59
 */

namespace System\Model;

use Think\Model;

class AuthConfigModel extends Model
{
    protected $_validate = array(
        //内置验证require不能为空
        array('merchant_id','require','代理商不能为空！',1),
        array('appid','require','AppID 不能为空！',1),
        array('secret','require','Secret 不能为空！',2)
      
    );


    // 列表
    function getList($param, $filed='c.*',$pageNum = 10){
        $page = isset($param['page'])? $param['page']: 1;
        $condition['c.is_del'] = 0;
        if(!empty($param['keyword'])){
            $condition['m.merchant_name'] = array('like',"%".$param['keyword']."%");
        }

        $total = $this->alias('c')->join('merchant m on m.merchant_id = c.merchant_id')->where($condition)->count();
        $Page = new \Think\PageAjax($total, $pageNum);
        $data['show'] = $Page->show(null);
        $data['Arrlist'] = $this->alias('c')->field($filed)
            ->join('merchant m on m.merchant_id = c.merchant_id')
            ->where($condition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('c.id DESC')->select();
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