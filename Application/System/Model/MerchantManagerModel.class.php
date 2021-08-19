<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:59
 */

namespace System\Model;

use Think\Model;

class MerchantManagerModel extends Model
{
    protected $_validate = array(
        //内置验证require不能为空
        array('account','require','登录账号不能为空！',1),
        array('real_name','require','管理员名称不能为空！',1),
        array('pwd','require','登录密码不能为空！',2),
        array('pwd_word', 'require', '确认密码不能为空',2),
        array('pwd','pwd_word','两次密码不一致', 2, 'confirm'),
        array('role_id', 'chkpriid', '必须选择权限', 1, 'callback'),
      
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
    function getList($param, $filed='c.*',$pageNum = 10){
        $page = isset($param['page'])? $param['page']: 1;
        $condition = [];
        if(!empty($param['keyword'])){
            $condition['c.real_name'] = array('like',"%".$param['keyword']."%");
        }

        $total = $this->alias('c')->where($condition)->count();
        $Page = new \Think\PageAjax($total, $pageNum);
        $data['show'] = $Page->show(null);
        $data['Arrlist'] = $this->alias('c')->field($filed)
            ->join('merchant m on m.merchant_id = c.merchant_id')
            ->join('role r on r.id = c.role_id', 'LEFT')
            ->where($condition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('c.id DESC')->select();
        return $data;
    }

    //
    function getInfo($id,$filed='*'){
        if($id){
            return $this->where(['id'=>$id])->field($filed)->find();
        }
    }

    //通过ID对会员组进行更新
    function updateInfoById($condition,$data){
        if($condition){
            return $this->where($condition)->save($data);
        }
    }
}