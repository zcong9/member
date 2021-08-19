<?php

namespace Member\Controller;

class AuthController
{

    public function index()
    {
        $mchId = I("param.mchId", 0);
        //保存 mcId
        session("member.mchId", $mchId);
        // 查询出数据库中的当前代理的对应的appid
        $public_number_set = D("public_number_set");
        $public_info = $public_number_set -> where(['merchant_id' => $mchId]) -> find();
        if (!$public_info) exit('公众号配置异常');
        $appid = $public_info['appid'];
        $HostURL = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php/member/auth/callback?mchId=' . $mchId;
        $redirect_uri = urlencode($HostURL);    // 获取到授权后要跳转到的地址
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
        header("location:" . $url);
    }

    # 接收由微信确认授权后传递过来的数据，然后注册或者去会员中心
    public function callback()
    {
        // 2、获取到网页授权的Access_token
        // 查询出数据库中的当前代理的对应的appid
        $mchId = I("param.mchId", 0);
        if ($mchId != session("member.mchId")) exit('mchId错误，请重新进入');

        $code = I("param.code/s", '', 'trim');
        if (!$code) exit('授权异常');

        // 查询出数据库中的当前代理的对应的appid
        $public_number_set = D("public_number_set");
        $public_info = $public_number_set -> where(['merchant_id' => $mchId]) -> find();

        if (!$public_info) exit('公众号配置异常');

        $appid = $public_info['appid'];
        $appsecret = $public_info['appsecret'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $appsecret . "&code=" . $code . "&grant_type=authorization_code";
        $resJson = http_get($url);
        $res = json_decode($resJson, true);

        if (!isset($res['access_token']) || !isset($res['openid'])) exit('access_token 获取失败');

        //openid
        session("member.openid", $res['openid']);

        $uInfo = $this -> getUserInfo($mchId, $res['access_token'], $res['openid']);
        if (empty($uInfo)) exit('登录异常请重新进入');

        if (isset($uInfo['phone']) && !empty($uInfo['phone'])) {
            header("location:" . U('Member/redirectReg'));
            exit;
        } else {
            header("location:" . U('Member/index'));
            exit;
        }
    }

    private function getUserInfo($mchId, $access_token, $openid)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
        $resultJson = http_get($url);
        $result = json_decode($resultJson, true);
        if (empty($result) || !isset($result['openid'])) return false;

        // 是否已登录绑定过
        $vipModel = M("members");

        //判断是否注册
        $data['merchant_id'] = $mchId;
        $data['avatar'] = isset($result['headimgurl']) && !empty($result['headimgurl']) ? $result['headimgurl'] : '';
        $data['nickname'] = isset($result['nickname']) && !empty($result['nickname']) ? $result['nickname'] : '';
        $data['sex'] = intval($result['sex']);
        $data['openid_wx'] = $openid;
        $data['unionid'] = isset($result['unionid']) && !empty($result['unionid']) ? $result['unionid'] : '';
        $country = isset($result['country']) && !empty($result['country']) ? $result['country'] : '未知';
        $province = isset($result['province']) && !empty($result['province']) ? $result['province'] : '未知';
        $city = isset($result['city']) && !empty($result['city']) ? $result['city'] : '未知';
        $data['addr'] = "{$country}|{$province}|{$city}";

        $where = ['merchant_id' => $mchId, 'openid_wx' => $openid, 'is_del' => 0];
        $item = $vipModel->where($where)->find();

        if (!empty($item)) {
            //修改
            $vipModel -> where(['id' => $item['id']]) -> save($data);
            session("USER", array(
                'id' => $item['id'],
                'openid' => $openid,
                'mchId' => $mchId
            ));

        } else {
            $data['create_at'] = time();
            //插入库
            $id = $vipModel -> add($data);
            session("USER", array(
                'id' => $id,
                'openid' => $openid,
                'mchId' => $mchId
            ));
        }

        return $data;
    }
}