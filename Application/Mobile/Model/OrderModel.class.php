<?php

namespace Mobile\Model;

use Think\Model;

class OrderModel
{
    public function __construct()
    {

    }

    public function getOrderSn()
    {
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $order_sn = $yCode[intval(date('Y')) - 2011] . '_' . strtoupper(dechex(date('m'))) . '_' . date('d') . '_' . substr(time(), -5) . '_' . substr(microtime(), 2, 5) . '_' . sprintf('%04d', rand(1000, 9999));
        return MD5($order_sn);
    }

    public function confirmOrderLog($msg, $dir = './mobile_confirm_order_log.log')
    {
        file_put_contents($dir, date('Y-m-d H:i:s') . ': ' . $msg . "\r\n", 8);
    }
}