<?php
/**
 * Created by PhpStorm.
 * User: Yino
 * Date: 2019/4/19
 * Time: 16:27
 */
namespace Admin\Model;
use Think\Model;
class ConfigModel extends Model{

    #获取店支付的相关配置
    public function selectConfigList($condition,$field = '*'){
        if($condition){
            return $this->field($field)->where($condition)->select();
        }
    }
}