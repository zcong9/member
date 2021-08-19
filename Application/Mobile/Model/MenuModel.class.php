<?php
namespace Mobile\Model;
use Think\Model;

class MenuModel extends Model{
    /**
     * 查找单条数据
     * 2018/8/7
     */
    public function shopsFind($condition)
    {
        $result = $this->where($condition)->find();
        return $result;
    }
    /**
     * 查找多条数据
     * 2018/8/7
     */
    public function shopsSelect($condition)
    {
        $result = $this->where($condition)->select();
        return $result;
    }

    /**
     * 删除单条数据
     * 2018/8/7
     */
    public function goodsDel($condition)
    {
        $id = $this->where($condition)->delete();
        return $id;
    }
    /**
     * 添加单条数据
     * 2018/8/7
     */
    public function goodsAdd($condition)
    {
        $result = $this->add($condition);
        return $result;
    }
    /**
     * 更新单条数据
     * 2018/8/7
     */
    public function goodsEdit($condition)
    {
        $result = $this->save($condition);
        return $result;
    }
    /**
     * 有分页查询
     * 2018/9/8
     */
    public function selectPage($condition,$p=1,$url='/index.php/Admin/Menu/menu',$page_num=5)
    {
        $result['result'] = $this->page($p,$page_num)->where($condition)->select();
        $count = count($this->where($condition)->select());
        //分页显示的字符串
        $result['page'] = $this->pe($url,$count,$p,$page_num);
        $result['count'] = ceil($count/$page_num);
        return $result;
    }

    /*
     * 分页拼接函数
     * */
    public function pe($url,$sum,$p,$pagesize){

        $start = ($p-1)*$pagesize;
        $allpage=ceil($sum/$pagesize);

        $prev=($p==1)?1:$p-1;
        $next=($p==$allpage) ? $allpage:$p+1;
        $page="";
        //$page.="<li><a href=' ".$url."/p/1' >首页</a></li>";
        $page.="<li><a href='".$url."/p/{$prev}'>上一页</a></li>";


        for($i=1;$i<=$allpage;$i++){

            if ($i==$p){
                $page.="<li class=\"active\" ><a  href='".$url."/p/{$i}'>{$i}</a></li>";

            }
            elseif ($i<3){
                $page.="<li><a  href='".$url."/p/{$i}'>{$i}</a></li>";
            }
            elseif ($i>$allpage-2){
                $page.="<li><a href='".$url."/p/{$i}'>{$i}</a></li>";

            }

        }
        $page.="<li><a href='".$url."/p/{$next}'>下一页</a></li>";
        //$page.="<li><a href='".$url."/p/{$allpage}'>尾页</a></li>";
        return $page;
    }

}