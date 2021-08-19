<?php

namespace Api\Services;

use Services\BaseServices;
use Common\Exceptions\ValidateException;
use Common\Model\MembersModel;

/**
 * 会员服务
 * Class MembersServices
 * @package Services
 */
class MembersServices extends BaseServices
{
    public function __construct()
    {
        $this->mod = new MembersModel();
    }

    public function getInfoByCode($code)
    {
        if (!in_array(substr($code, 0, 2), ['FP'])) throw new ValidateException('二维码错误', 0);
        $decryptStr = (new \Think\Encrypt())->decrypt(substr($code, 2), C("SECRET_KEY"));
        $arr = explode('|', $decryptStr);
        if (count($arr) != 2) throw new ValidateException('二维码错误解析异常', 0);
        if (($arr[0] + 30) < time()) throw new ValidateException('二维码已失效', 0);
        $info = $this->mod->get([$this->mod->getPk() => (int)$arr[1], 'merchant_id' => $this->getMchId()]);
        if (empty($info)) throw new ValidateException('获取会员信息失败', 0);
        return $info;
    }
}
