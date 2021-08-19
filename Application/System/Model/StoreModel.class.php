<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:59
 */

namespace System\Model;

use Think\Model;

class StoreModel extends Model
{
    protected $_validate = array(
        //内置验证require不能为空
        array('store_name','require','门店名称不能为空',1),
        array('store_addr','require','地址不能为空！',1),
    );

    // 列表
    function getList($param, $filed='s.*',$pageNum = 10){
        $page = isset($param['page'])? $param['page']: 1;

        $condition = [];
        if(!empty($param['keyword'])){
            $condition['s.store_name'] = array('like',"%".$param['keyword']."%");
        }
        $total = $this->alias('s')->where($condition)->count();
        $Page = new \Think\PageAjax($total, $pageNum);
        $data['show'] = $Page->show(null);
        $data['Arrlist'] = $this->alias('s')->field($filed)
                            ->join('merchant m on m.merchant_id = s.merchant_id')
                            ->where($condition)
                            ->limit($Page->firstRow . ',' . $Page->listRows)
                            ->order('store_id DESC')
                            ->select();
        return $data;
    }

    //
    function getInfo($id,$filed='*'){
        if($id){
            return $this->where(['store_id'=>$id])->field($filed)->find();
        }
    }

    //通过ID对会员组进行更新
    function updateInfoById($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }
}