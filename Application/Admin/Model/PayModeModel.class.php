<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 16:28
 */
namespace Admin\Model;
use Think\Model;
class PayModeModel extends Model{

    #获取状态开关
    public function findPayModeInfo($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->find();
        }
    }

    #添加支付状态开关
    public function addPayMode($data){
        return $this->add($data);
    }

    #修改支付状态
    public function savePayMode($condition,$data){
       if($condition){
           return $this->where($condition)->save($data);
       }
    }
}