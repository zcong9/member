<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:59
 */

namespace System\Model;

use Think\Model;

class MerchantModel extends Model
{
    protected $_validate = array(
        //内置验证require不能为空
        array('merchant_name','require','商户名称不能为空',1),
        array('contact_name','require','联系人不能为空',1),
        array('contact_phone','require','联系电话不能为空！',1),
        array('contact_phone','/^(0|86|17951)?(13[0-9]|14[0-9]|15[0-9]|16[0-9]|17[0-9]|18[0-9]|19[0-9])[0-9]{8}$/','手机号格式不正确',0),
        array('address','require','联系地址不能为空！',1),
    );

    protected function chkpriid($data)
    {
        if (empty($data)) {
            return false;
        }else{
            return true;
        }
    }

    // 列表
    function getList($param, $filed='*',$pageNum = 10){
        $page = isset($param['page'])? $param['page']: 1;

        $condition = [];
        if(!empty($param['keyword'])){
            $condition['nickname'] = array('like',"%".$param['keyword']."%");
        }
        $total = $this->where($condition)->count();
        $Page = new \Think\PageAjax($total, $pageNum);
        $data['show'] = $Page->show(null);
        $data['Arrlist'] = $this->field($filed)
                            ->where($condition)
                            ->limit($Page->firstRow . ',' . $Page->listRows)
                            ->order('merchant_id DESC')
                            ->select();
        return $data;
    }

    //
    function getInfo($id,$filed='*'){
        if($id){
            return $this->where(['merchant_id'=>$id])->field($filed)->find();
        }
    }

    //通过ID对会员组进行更新
    function updateInfoById($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }
}