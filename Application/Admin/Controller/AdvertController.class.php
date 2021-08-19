<?php
/**
 * 会员控制器
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/10
 * Time: 10:04
 */
namespace Admin\Controller;
class AdvertController extends BaseController
{
    //获取会员
    public function index()
    {
        $page = I("param.page",1,'int');
        $condition['merchant_id'] = $this->merchant_id;
        $ad = D('advertisement_vip');
        $count = $ad->where($condition)->count();
        $Page = new \Think\Page($count,10);
        $show = $Page->show();
        $lists = $ad->field('*')->where($condition)->page($page,10)->order('advertisement_id DESC')->select();
        $this->assign('lists', $lists);
        $this->assign('page',$show);
        $this->display();
    }

    public function addqrUpload(){
        $data = I('post.');
        if(empty($data['advertisement_id'])){
            $upload           = new \Think\Upload(); // 实例化上传类
            $upload->maxSize  = 3145728; // 设置附件上传大小
            $upload->exts     = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            #如果碰上无法上传，出现根目录找不到的情况，可能是文件夹权限导致
            $upload->savePath = "/Public/advert/";
            $upload->rootPath = './'; // 设置附件上传根目录  必须设置
            #上传单个文件
            $info = $upload->uploadOne($_FILES['img_url']);
            if (!$info) {
            # 上传错误提示错误信息
               $this->error($upload->getError());
            }
            // 上传成功 获取上传文件信息
            $data['advertisement_image_url']          = $info['savepath'] . $info['savename'];
            $data['merchant_id'] = $this->merchant_id;
            unset($data['advertisement_id']);
            $res = D('advertisement_vip')->add($data);
        }else{
            if(isset($_FILES['img_url']) && !empty($_FILES['img_url']['size'])){
                $upload           = new \Think\Upload(); // 实例化上传类
                $upload->maxSize  = 3145728; // 设置附件上传大小
                $upload->exts     = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                #如果碰上无法上传，出现根目录找不到的情况，可能是文件夹权限导致
                $upload->savePath = '/Public/advert/';
                $upload->rootPath = './'; // 设置附件上传根目录  必须设置
                #上传单个文件
                $info = $upload->uploadOne($_FILES['img_url']);
                if (!$info) {
                # 上传错误提示错误信息
                   $this->error($upload->getError());
                }
                // 上传成功 获取上传文件信息
                $data['advertisement_image_url']          = $info['savepath'] . $info['savename'];
            }
            $res = D('advertisement_vip')->save($data);
        }
        
        $this->success('操作成功');

    }
   
   public function edit(){
        $id = I('get.id');
        $res = D('advertisement_vip')->where(['advertisement_id'=>$id])->find();
        echo json_encode($res);
   }

   public function delInfo(){
        $id = I('post.id');
        $info = D('advertisement_vip')->where(['advertisement_id'=>$id])->find();
        if(!empty($info['advertisement_image_url'])){
            @unlink('.'.$info['advertisement_image_url']);
        }
        $res = D('advertisement_vip')->where(['advertisement_id'=>$id])->delete();
        $this->success('操作成功');
   }
}
