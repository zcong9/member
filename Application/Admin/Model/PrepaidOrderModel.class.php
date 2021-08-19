<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 15:32
 */
namespace Admin\Model;
use Think\Model;
class PrepaidOrderModel extends Model
{
    #查询当前店铺的打印机
    public function getPrepaidOrderList($param,$field = 'o.*', $page = 1,$pageNum = 10){
        $condition['o.merchant_id'] = $param['merchant_id'];
        if(!empty($param['keyword'])){
            $condition['o.order_sn'] = array('like',"%".$param['keyword']."%");
        }
        $count = $this->alias('o')->join('members v on v.id = o.member_id')->where($condition)->count();
        $Page = new \Think\Page($count,$pageNum);
        $data['show'] = $Page->show();
        $data['Arrlist'] = $this->alias('o')->field($field)
            ->join('members v on v.id = o.member_id')
            ->where($condition)
            ->page($page,$pageNum)
            ->order('o.create_at DESC')
            ->select();
        return $data;
    }

}