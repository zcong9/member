<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 15:32
 */
namespace System\Model;
use Think\Model;
class PrepaidOrderModel extends Model
{
    #查询当前店铺的打印机
    public function getPrepaidOrderList($param,$field = 'o.*',$pageNum = 10){
        $condition = [];
        if(!empty($param['merchant_id'])){
            $condition['o.merchant_id'] = $param['merchant_id'];
        }
        if(!empty($param['keyword'])){
            $condition['o.order_sn'] = array('like',"%".$param['keyword']."%");
        }
        $total = $this->alias('o')->join('members v on v.id = o.member_id')->where($condition)->count();
        $Page = new \Think\PageAjax($total, $pageNum);
        $data['show'] = $Page->show(null);
        $data['Arrlist'] = $this->alias('o')->field($field)
            ->join('members v on v.id = o.member_id')
            ->join('merchant m on m.merchant_id = o.merchant_id')
            ->where($condition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('o.create_at DESC')
            ->select();
        return $data;
    }

}