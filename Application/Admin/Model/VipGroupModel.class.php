<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/5/7
 * Time: 17:21
 */

namespace Admin\Model;
use Think\Model;

class VipGroupModel extends Model
{
    # 获取会员组信息
    public function selectVipGroup($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->select();
        }
    }

}