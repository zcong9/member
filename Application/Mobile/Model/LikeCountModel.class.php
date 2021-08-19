<?php
namespace Mobile\Model;
use Think\Model;

//商家私有表
class LikeCountModel extends Model{
    /**
     * 菜品综合查询
     * 2018/9/8
     * $condition  查询条件
     */
    public function likeSearch($condition)
    {
        if ($condition['startDate']||$condition['endDate']||$condition['startTime']||$condition['endTime']) {
            $startDate = $condition['startDate'];
            $endDate = $condition['endDate'];
            $startTime = $condition['startTime'];
            $endTime = $condition['endTime'];
            $startDate = empty($startDate) ? date('Y-m-d', time()) : $startDate;
            $endDate = empty($endDate) ? $startDate : $endDate;
            $startTime = empty($startTime) ? '00:00:00' : $startTime;
            $endTime = empty($endTime) ? '23:59:59' : $endTime;
            $startTimeStr = strtotime($startDate . " " . $startTime);
            $endTimeStr = strtotime($endDate . " " . $endTime);
            $where['add_time'] = array("between", array($startTimeStr, $endTimeStr));
        }
        $where['restaurant_id'] = $condition['restaurant_id'];
        //菜品点赞总数目
        $result['ordersNum'] = count($this->where($where)->distinct(true)->field('order_sn')->select());

        return $result;
    }
    /**
     * 查询所有记录
     * 2018/9/8
     */
    public function selectAll($condition)
    {
        $result = $this->where($condition)->select();
        return $result;
    }
    /**
     * 查询被点赞过的菜品集合
     * 2018/9/8
     * $p   页码
     * $url 分页跳转路径
     * $page_num  页面显示记录条数
     */
    public function likeSn($condition,$p=1,$url='/Admin/Like/likeCount',$page_num=5)
    {
        if ($condition['startDate']||$condition['endDate']||$condition['startTime']||$condition['endTime']) {
            $startDate = $condition['startDate'];
            $endDate = $condition['endDate'];
            $startTime = $condition['startTime'];
            $endTime = $condition['endTime'];
            $startDate = empty($startDate) ? date('Y-m-d', time()) : $startDate;
            $endDate = empty($endDate) ? $startDate : $endDate;
            $startTime = empty($startTime) ? '00:00:00' : $startTime;
            $endTime = empty($endTime) ? '23:59:59' : $endTime;
            $startTimeStr = strtotime($startDate . " " . $startTime);
            $endTimeStr = strtotime($endDate . " " . $endTime);
            $where['add_time'] = array("between", array($startTimeStr, $endTimeStr));
        }
        $where['restaurant_id'] = $condition['restaurant_id'];
        //该店铺被点赞的菜品总数
        $count = count($this->where($where)->distinct(true)->field('goods_id')->select());
        $goods_id = $this->page($p,$page_num)->where($where)->distinct(true)->field('goods_id')->order('goods_id desc')->select();
        foreach ($goods_id as $k=>$v){
            $r = $this->where($v)->select();
            $middle = $r[0];
            $middle['num'] = count($r);
            $result['result'][] = $middle;
        }

        //分页显示的字符串
        $result['page'] = get_page($url,$count,$p,$page_num);
        $result['count'] = ceil($count/$page_num);
        return empty($result['result'])?null:$result;
    }
}