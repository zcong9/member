<?php
namespace System\Controller;
use Think\Controller;
use System\Model\AccountLogModel;
use System\Model\MerchantModel;
/**
 * Class BaseController
 * @package System\Controller
 */
class PayLogController extends Controller
{
    //验证是否登录
    public function index()
    {
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getList([], 'merchant_id, merchant_name', 99999);
        $this->assign('merchantlist', $merchant['Arrlist']);
        $accountLog = new AccountLogModel();
        $param = I('param.');
        if (IS_AJAX) {
            $param = I('param.');
            $data = $accountLog->getlogList($param, 'a.*, v.nickname, m.merchant_name');
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
        $lists = $accountLog->getlogList($param, 'a.*, v.nickname, m.merchant_name');
        $this->assign('lists', $lists['Arrlist']);
        $this->assign('page',$lists['show']);
        return $this->display();
    }
}
