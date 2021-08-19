<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 16:30
 */
namespace Admin\Model;
use Think\Model;
class OrdinaryPayModel extends Model{
    #
    public function findOrdinaryPay($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->find();
        }
    }
}
