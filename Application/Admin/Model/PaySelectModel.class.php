<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 16:27
 */
namespace Admin\Model;
use Think\Model;
class PaySelectModel extends Model{

    #获取各支付方式开关状态
    public function selectPaySelectList($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->select();
        }
    }

    #修改支付开关状态
    public function savePaySelect($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }
}