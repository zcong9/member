<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 10:20
 */
namespace Admin\Model;
use Think\Model;
class RestaurantBillModel extends Model
{
    #获取票据开关详情
    public function getRestaurantBillInfo($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->find();
        }
    }

    #修改票据开关
    public function changeBillStatus($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }
}