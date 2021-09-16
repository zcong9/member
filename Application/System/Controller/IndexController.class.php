<?php
namespace System\Controller;
use System\Model\MembersModel;
class IndexController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        //$this->checkLogin();
    }

	public function index()
    {
    	//$treeMenu = $this->treeMenu();
        $treeMenu= [];
        $this->assign('treeMenu', $treeMenu);
        return $this->display();
    }
	
	
	public function home(){
        // 月份天数
        $startDate = I('get.startDate');
        $tmpDay = !empty($startDate) ? $startDate: date('Y-m');
        $days = date('t', strtotime($tmpDay));
        $membersModel = new MembersModel();
        for($i = 1; $i<= $days; $i++){
            $day_data[] = $i;
            $startTimeStr = strtotime($tmpDay.'-'.$i.' 00:00:00');
            $endTimeStr = strtotime($tmpDay.'-'.$i.' 23:59:59');
            $user_data[] = $membersModel->where("create_at BETWEEN $startTimeStr AND $endTimeStr ")->count();

        }
        $day_str = implode(',', $day_data);
        $this->assign('day_str', $day_str);

        $user_str = implode(',', $user_data);
        $this->assign('user_str', $user_str);
        $this->assign('tmpDay', $tmpDay);
        return $this->display();
	}
}



