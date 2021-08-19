<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/18
 * Time: 17:28
 */
namespace Admin\Model;
use Think\Model;
class RestaurantManagerModel extends Model{

    #修改店铺信息
    public function saveRestaurantManagerInfo($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }

    #查询店铺帐号记录
    public function selectRestaurantManager($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->select();
        }
    }

    #查询店铺单个帐号记录
    public function findRestaurantManager($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->find();
        }
    }

    #添加店铺后台帐号
    public function addRestaurantManager($data){
        return $this->add($data);
    }

    #修改店铺后台帐号
    public function saveRestaurantManager($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }

    #删除店铺后台帐号
    public function delPermission($condition){
        if($condition){
            return $this->where($condition)->delete();
        }
    }

}