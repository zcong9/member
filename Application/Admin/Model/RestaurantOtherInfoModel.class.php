<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 17:29
 */
namespace Admin\Model;
use Think\Model;
class RestaurantOtherInfoModel extends Model
{
    #获取当面付配置
    public function findRestaurantOtherInfo($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->find();
        }
    }


}