<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:59
 */

namespace Admin\Model;

use Think\Model;

class MembersModel extends Model
{
    protected  $connection = 'Members';

    //获取会员的记录数
    function getVipCount($condition){
        return $this->where($condition)->count();
    }

    //会员列表分页
    function getVipListPage($condition,$p = 1,$pageNum = 10){
        if($condition){
            return $this->where($condition)->LIMIT($p,$pageNum)->SELECT();
        }
    }

    //根据ID查询生日记录
    function checkBirthdayById($condition){
        if($condition){
            return $this->where($condition)->getField("birthday");
        }
    }

    //通过ID对会员组进行更新
    function updateVipInfoById($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }

    //会员列表
    function getVipList($param, $filed='*', $page = 1,$pageNum = 10){
        $condition['is_del'] = 0;
        $condition['merchant_id'] = $param['merchant_id'];
        if(!empty($param['name_key'])){
            $condition['nickname'] = array('like',"%".$param['name_key']."%");
        }
        $count = $this->where($condition)->count();
        $Page = new \Think\Page($count,$pageNum);
        $data['show'] = $Page->show();
        $data['Arrlist'] = $this->field($filed)->where($condition)->page($page,$pageNum)->order('id DESC')->select();
        return $data;
    }

    //获取会员信息
    function getVipInfo($condition, $filed='*'){
        return $this->field($filed)->where($condition)->find();
    }

    //会员列表
    function getVipAll($param, $filed='*'){
        $condition['is_del'] = 0;
        $condition['merchant_id'] = $param['merchant_id'];
        if(!empty($param['name_key'])){
            $condition['nickname'] = array('like',"%".$param['name_key']."%");
        }

        $data = $this->field($filed)->where($condition)->order('id DESC')->select();
        return $data;
    }
}