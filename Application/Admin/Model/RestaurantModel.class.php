<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/18
 * Time: 15:10
 */
namespace Admin\Model;
use Think\Model;
class RestaurantModel extends Model{

    #联表查询店铺的基本信息和店铺的帐号密码
    public function  getRestaurantInfo($condition,$field = '*'){
        if($condition){
            return $this->alias('r')->join('restaurant_manager m ON m.restaurant_id = r.restaurant_id')->field($field)->where($condition)->find();
        }
    }

    #查询店铺信息
    public function getRestaurant($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->find();
        }
    }

    #修改店铺信息
    public function saveRestaurantInfo($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }

}