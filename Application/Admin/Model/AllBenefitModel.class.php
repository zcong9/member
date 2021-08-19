<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 15:32
 */
namespace Admin\Model;
use Think\Model;
class AllBenefitModel extends Model
{
    #获取预充值列表
    public function getPrepaidList($param,$field = '*'){
        if(!empty($param)) {
            return $this->field($field)
                ->where($param)
                ->order('id DESC')
                ->select();
        }
    }

    //查询预充值信息
    function checkPrepaid($condition){
        return $this->where($condition)->getField("id");
    }

    //通过ID更新预充值信息
    function updatePrepaidById($condition,$data){
        return $this->where($condition)->save($data);
    }

    //添加预充值记录
    function addPrepaid($data){
        return $this->add($data);
    }

    //根据ID对预充值记录进行删除
    function delPrepaidById($condition){
        if($condition){
            return $this->where($condition)->delete();
        }
    }

    //
    function findBenefit($condition){
        if($condition){
            return $this->field('benefit,account,id')->where($condition)->find();
        }
    }




}