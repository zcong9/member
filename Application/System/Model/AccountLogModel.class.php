<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 15:32
 */
namespace System\Model;
use Think\Model;
class AccountLogModel extends Model
{
    #查询当前店铺的打印机
    public function getlogList($param,$field = 'a.*',$pageNum = 10){
        $page = isset($param['page'])? $param['page']: 1;
        $condition = [];
        if(!empty($param['merchant_id'])){
            $condition['a.merchant_id'] = $param['merchant_id'];
        }
        if(!empty($param['keyword'])){
            $condition['a.order_sn'] = array('like',"%".$param['keyword']."%");
        }
        $total = $this->alias('a')->join('merchant m on m.merchant_id = a.merchant_id')->where($condition)->count();
        $Page = new \Think\PageAjax($total, $pageNum);
        $data['show'] = $Page->show(null);
        $data['Arrlist'] = $this->alias('a')->field($field)
            ->join('merchant m on m.merchant_id = a.merchant_id')
            ->join('members v on v.id = a.member_id')
            ->where($condition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('a.create_at DESC')
            ->select();
        return $data;
    }

}