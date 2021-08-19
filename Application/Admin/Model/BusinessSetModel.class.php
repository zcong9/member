<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 15:32
 */
namespace Admin\Model;
use Think\Model;
class BusinessSetModel extends Model
{
    public function getBusinessSetInfo($condition){
        if($condition){
            return $this->where($condition)->find();
        }
    }
    //通过ID进行添加或更新
    public function updatePrepaidById($condition,$data){
        if($condition){
             $res = $this->getBusinessSetInfo($condition);
//            return $this->getLastSql();
            if($res){
                unset($data['merchant_id']);
                unset($data['type']);
                return $this->where($condition)->save($data);
            }else{
                return $this->add($data);
            }
        }
    }
}