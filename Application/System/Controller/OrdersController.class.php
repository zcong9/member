<?php
namespace System\Controller;
use Think\Controller;
use System\Model\PrepaidOrderModel;
use System\Model\MerchantModel;
/**
 * Class BaseController
 * @package System\Controller
 */
class OrdersController extends Controller
{

    //
    public function index()
    {   
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getList([], 'merchant_id, merchant_name', 99999);
        $this->assign('merchantlist', $merchant['Arrlist']);
        $prepaidOrder = new PrepaidOrderModel();
        $param = I('param.');
        if (IS_AJAX) {
        
            $data = $prepaidOrder->getPrepaidOrderList($param, 'o.*, v.nickname, m.merchant_name');
            $this->assign('page', $data['show']);
            $this->assign('lists', $data['Arrlist']);
            $this->display('ajax.index');
            exit;
        }
    
        $lists = $prepaidOrder->getPrepaidOrderList($param, 'o.*, v.nickname, m.merchant_name');
        $this->assign('lists', $lists['Arrlist']);
        $this->assign('page',$lists['show']);
        return $this->display();
    }
}
