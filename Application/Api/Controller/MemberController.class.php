<?php

namespace Api\Controller;

use Common\Extend\Reponse;
use Common\Exceptions\ApiException;
use Api\Services\OrdersServices;
use Api\Services\PayServices;
use Api\Services\MembersServices;

class MemberController extends BaseController
{
    /**
     * 消费
     */
    public function consume()
    {
        $post = $this->params['data'];
        $memberInfo = (new MembersServices())->setMchId($this->mchId)->getInfoByCode($post['code']);
        $this->memberId = $memberInfo['id'];
        $orderId = (new OrdersServices())->setMchId($this->mchId)
            ->setStoreId($this->storeId)
            ->setMemberId($this->memberId)
            ->create($post);
        $result = (new PayServices())->pay(['order_id' => $orderId,'code' => $post['code']]);
        if (empty($result)) throw new ApiException('消费失败', 0);
        exit(Reponse::jsonStr(1, '消费成功', $result));
    }

    /**
     * 撤销消费
     */
    public function consumeCancel()
    {
        $post = $this->params['data'];
        $orderId = (new OrdersServices())->setMchId($this->mchId)
            ->setStoreId($this->storeId)
            ->refund($post);
        $result = (new PayServices())->refund(['order_id' => $orderId]);
        if (empty($result)) throw new ApiException('撤销失败', 0);
        exit(Reponse::jsonStr(1, '撤销成功', $result));
    }

}