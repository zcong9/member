<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Page;

	class AccountsController extends Controller{

		public function __construct(){
			Controller::__construct();
			$admin_id = session("re_admin_id");
			if(!$admin_id){
				redirect("Index/login");
			}
			$restaurant_manager_model = D('restaurant_manager');
			$restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
			session('restaurant_id',$restaurant_id);
		}


		# 角色详情
        public function role(){
            # 根据登录的用户id，获取所属角色
            $condition['id']   =   session('re_admin_id');
            $role    =   M('restaurant_manager')->field('role_id')->where($condition)->find();

            # 通过parent_id获取属于该角色的下级角色列表
            $where['parent_id']    =   $role['role_id'];
            $where['restaurant_id']    =   session('restaurant_id');
            $count =  M('role')->where($where)->count();
            $pageNum = 13;
            $Page = new \Think\Page($count,$pageNum);
            $show = $Page->show();
            $this->assign('page',$show);
            $p = I("param.page");
            # 获取下级的所有角色
            $roleList   =   M('role')->field('id,role_name,role_desc')->where($where)->page($p,$pageNum)->select();
            $this->assign("roleList",$roleList);

            # 通过role_id获取权限列表
            $map['id']    =   $role['role_id'];
            $roleInfo =   M('role')->field('action_list')->where($map)->find();
            $arr = explode(',',$roleInfo['action_list']);
            $menu = array();
            # 获取当前帐号权限详情，点出添加或编辑时，填充权限详情
            foreach ($arr as $k=>$v){
                $map['id'] = $v;
                $menu[$k] =   M('action')->field('action_name,id as action_id')->where($map)->find();
            }
            $this->assign('menu',$menu);
            $this->display();
        }

        public function addEditRole(){
		    if($_POST) {
                $roleModel = M('role');
                $roleModel->startTrans();
                $id = trim(I('post.id'));
                $data['role_name'] = trim(I('post.role_name'));
                $data['role_desc'] = trim(I('post.role_desc'));
                $actList = I('post.action_list');
                $data['action_list'] = '';
                # 当其转换成字符串拼接的形式储存进数据库
                foreach ($actList as $k => $v) {
                    # 如果该数据为该数组最大时，不需要添加,
                    if (count($actList) == $k + 1) {
                        $data['action_list'] .= $v;
                    } else {
                        $data['action_list'] .= $v . ",";
                    }
                }

                # 查询该帐号是否是店铺超级管理员，如果不是管理员，无法管理角色。保证role表不出现无限分层的情况
                $condition1['id'] = session('re_admin_id');
                $restaurantData = M('restaurant_manager')->field('business_id,role_id,is_main')->where($condition1)->find();
                if ($restaurantData['is_main'] != '1') {
                    if ($id) {
                        $condition['role_name'] = $data['role_name'];
                        $condition['restaurant_id'] = session('restaurant_id');
                        $condition['id'] = array('NEQ', $id);
                        # 查看该店铺下除了本条记录，是否还设置了相同名字的角色
                        $roleRes = $roleModel->field('role_name')->where($condition)->find();
                        if ($roleRes) {
                            $msg['msg'] = "操作失败，该角色名称已存在！";
                            $msg['code'] = 10002;
                        }

                        # 修改当前店铺设置的角色信息及权限
                        $where['id'] = $id;
                        $editRes = $roleModel->where($where)->save($data);
                        if ($editRes) {
                            $map['restaurant_id'] = session('restaurant_id');
                            $count = $roleModel->where($map)->count();
                            $pageNum = 13;
                            $page = ceil($count / $pageNum);
                            $msg['code'] = 10000;
                            $msg['msg'] = "编辑角色成功！";
                            $msg['page'] = $page;
                        } else {
                            $map['restaurant_id'] = session('restaurant_id');
                            $count = $roleModel->where($map)->count();
                            $pageNum = 13;
                            $page = ceil($count / $pageNum);
                            $msg['code'] = 10000;
                            $msg['msg'] = "编辑角色失败！";
                            $msg['page'] = $page;
                            $roleModel->rollback();
                        }
                    } else {
                        $data['business_id'] = $restaurantData['business_id'];
                        $data['parent_id'] = $restaurantData['role_id'];
                        $data['restaurant_id'] = session('restaurant_id');

                        # 根据代理id,店铺id和角色名称，如果存在该条件的数据，则无法添加
                        $condition['role_name'] = $data['role_name'];
                        $condition['business_id'] = $data['business_id'];
                        $condition['restaurant_id'] = $data['restaurant_id'];
                        $roleRes = M('role')->field('role_name')->where($condition)->find();
                        if ($roleRes) {
                            $msg['msg'] = "操作失败，该角色名称已存在！";
                            $msg['code'] = 10002;
                        }
                        $addRes = $roleModel->add($data);
                        if ($addRes) {
                            $map['restaurant_id'] = session('restaurant_id');
                            $count = $roleModel->where($map)->count();
                            $pageNum = 13;
                            $page = ceil($count / $pageNum);
                            $msg['code'] = 10000;
                            $msg['msg'] = "新增角色成功！";
                            $msg['page'] = $page;
                        } else {
                            $map['restaurant_id'] = session('restaurant_id');
                            $count = $roleModel->where($map)->count();
                            $pageNum = 13;
                            $page = ceil($count / $pageNum);
                            $msg['code'] = 10000;
                            $msg['msg'] = "新增角色失败！";
                            $msg['page'] = $page;
                            $roleModel->rollback();
                        }
                    }
                } else {
                    $msg['msg'] = "操作失败，请联系店铺管理员添加角色！";
                    $msg['code'] = 10002;
                }
                $roleModel->commit();
                exit(json_encode($msg));
            }
        }

        # 编辑角色时填充数据
        public function modifyRole(){
		    if($_GET){
                $condition['id']    =   trim(I('id'));
                $object   =   M('role')->field('id,role_name,role_desc,parent_id,action_list,business_id,restaurant_id')->where($condition)->find();
                $arr = explode(',',$object['action_list']);
                $menu = array();
                foreach ($arr as $k=>$v){
                    $where['id'] = $v;
                    $menu[$k] =   M('action')->field('action_name,id as role_id')->where($where)->find();
                }
                $object['menu'] =   $menu;
                exit(json_encode($object));
            }
        }

        # 删除角色
        public function delRole(){
            if($_POST){
                $condition['id']            =   trim(I('id'));
                if($condition['id'] == null){
                    $msg['code'] = 10003;
                    $msg['msg'] = "删除失败，id不能为空！";
                    exit(json_encode($msg));
                }else{
                    # 查看该角色是否存在下级角色
                    $where['parent_id']    =   trim(I('id'));
                    $roleData = M('role')->field('role_name')->where($where)->find();
                    if($roleData){
                        $msg['code'] = 10002;
                        $msg['msg'] = "删除失败！请先删除下级角色";
                        exit(json_encode($msg));
                    }

                    # 遍历该角色是否有关联到店铺，如果有关联，无法直接删除
                    $condition1['role_id']  =   trim(I('id'));
                    $managerData    =   M('restaurant_manager')->field('role_id')->where($condition1)->find();
                    if($managerData){
                        $msg['code'] = 10003;
                        $msg['msg'] = "删除失败，该角色已关联帐号！";
                        exit(json_encode($msg));
                    }

                    # 查询该帐号是否是店铺超级管理员，如果不是管理员，无法管理角色。保证role表不出现无限分层的情况
                    $condition1['id'] = session('re_admin_id');
                    $isMian = M('restaurant_manager')->where($condition1)->getField('is_main');
                    if($isMian === 1){
                        $msg['code'] = 10003;
                        $msg['msg'] = "删除失败，请联系店铺管理员进行删除！";
                        exit(json_encode($msg));
                    }

                    $roleRes = M('role')->where($condition)->delete();
                    if($roleRes){
                        $where['restaurant_id']   =   session('restaurant_id');
                        $count =  M('role')->where($where)->count();
                        $pageNum = 13;
                        $page = ceil($count/$pageNum);
                        $msg['code'] = 10000;
                        $msg['msg'] = "删除成功！";
                        $msg['page'] = $page;
                        exit(json_encode($msg));
                    }else{
                        $msg['code'] = 10002;
                        $msg['msg'] = "删除失败！";
                        exit(json_encode($msg));
                    }
                }
            }
        }

		public function account(){

            $where['id']   =   session('re_admin_id');
            $role    =   M('restaurant_manager')->field('role_id')->where($where)->find();

            # 通过parent_id获取属于该角色的下级角色列表
            $condition['parent_id']    =   $role['role_id'];



		    $condition['restaurant_id'] =   session('restaurant_id');
		    $roleList   =   M('role')->field('id,role_name,role_desc,parent_id,action_list,business_id,restaurant_id')->where($condition)->select();
		    $this->assign('roleList',$roleList);

		    # 获取该店铺下的所有帐号
            $condition['is_main']   =   1;
            $accountList    =   M('restaurant_manager')->field('id,manager_name,login_account,role_id')->where($condition)->select();
            $this->assign('accountList',$accountList);
		    $this->display();
        }

        # 添加或编辑帐号
        public function addEditAccount(){
		    if($_POST){
                $roleModel                  =   M('role');
		        $data['manager_name']       =   trim(I('post.manager_name'));
                $data['login_account']      =   trim(I('post.login_account'));
                $data['role_id']            =   trim(I('post.role_id'));
                $password                   =   trim(I('post.password'));
                $passwords                  =   trim(I('post.passwords'));
                $id                         =   trim(I('post.id'));
                # 如果存在id，则进行修改  否则进行新增
		        if($id){
		            # 验证两个密码是否一致，不一致的话   直接退出
                    if($password != $passwords){
                        $msg['code'] = 10004;
                        $msg['msg'] = "操作失败，密码不一致！";
                        exit(json_encode($msg));
                    }
                    # 查询除该记录外是否还有相同帐号已注册
                    $condition['login_account'] = $data['login_account'];
                    $condition['id'] = array('NEQ',$id);
                    $is_presence = M('restaurant_manager')->field('login_account')->where($condition)->find();
                    if ($is_presence) exit(json_encode(['code'=>10002,'msg'=>'用户名重复!']));

                    //查询该帐号的密码，用于验证密码字段是否要加入修改
                    $condition1['login_account'] = $data['login_account'];
                    $condition1['id'] = array('EQ',$id);
                    $pwd = M('restaurant_manager')->where($condition1)->getField('password');
                    if($password !== $pwd) {
                        //TODO
                        # 添加修改密码日志
                        $RestaurantSetting = new RestaurantSettingController();
                        $RestaurantSetting->addChangePwdLog(session('login_account'),$data['login_account'],$pwd,md5($password));
                        $checkRule = checkPwdRule($password);
                        if ($checkRule['code'] !== 0) {
                            $data['password']           =   md5($password);
                        } else {
                            exit(json_encode($checkRule));
                        }
                    }

                    $map['id']    =   $id;
                    $editRes =   M('restaurant_manager')->where($map)->save($data);
                    if($editRes !== false){
                        $map['restaurant_id'] = session('restaurant_id');
                        $count =  M('restaurant_manager')->where($map)->count();
                        $pageNum = 13;
                        $page = ceil($count/$pageNum);
                        $msg['code'] = 10000;
                        $msg['msg'] = "编辑帐号成功！";
                        $msg['page'] = $page;
                        exit(json_encode($msg));
                    }else{
                        $map['restaurant_id'] = session('restaurant_id');
                        $count =  M('restaurant_manager')->where($map)->count();
                        $pageNum = 13;
                        $page = ceil($count/$pageNum);
                        $msg['code'] = 10000;
                        $msg['msg'] = "未做任何修改！";
                        $msg['page'] = $page;
                        exit(json_encode($msg));
                    }
                }else{
                    # 验证两个密码是否一致，不一致的话   直接退出
                    if($password != $passwords){
                        $msg['code'] = 10004;
                        $msg['msg'] = "操作失败，密码不一致！";
                        exit(json_encode($msg));
                    }
                    # 查询除该记录外是否还有相同帐号已注册
                    $condition['login_account'] = $data['login_account'];
                    $condition['id'] = array('NEQ',$id);
                    $is_presence = M('restaurant_manager')->field('login_account')->where($condition)->find();
                    if ($is_presence) exit(json_encode(['code'=>10002,'msg'=>'用户名重复!']));
                    $data['password']           =   md5($password);

                    $data['restaurant_id']      =   session('restaurant_id');
                    $condition['restaurant_id'] =   $data['restaurant_id'];
                    $restaurantData =   M('restaurant')->field('business_id')->where($condition)->find();
                    $data['business_id']            =   $restaurantData['business_id'];
                    # is_main为1时   为非超级管理员
                    $data['is_main']   =   1;
                    $addRes =   M('restaurant_manager')->add($data);
                    if($addRes){
                        $map['restaurant_id'] = session('restaurant_id');
                        $count =  M('restaurant_manager')->where($map)->count();
                        $pageNum = 13;
                        $page = ceil($count/$pageNum);
                        $msg['code'] = 10000;
                        $msg['msg'] = "新增帐号成功！";
                        $msg['page'] = $page;
                        exit(json_encode($msg));
                    }
                }
            }
        }

        public function modifyAccount(){
		    if($_GET){
                $condition['id']   =   trim(I('id'));
                $accountList    =   M('restaurant_manager')->field('id,manager_name,login_account,role_id,password')->where($condition)->find();
                exit(json_encode($accountList));
            }
        }

        public function delAccount(){
		    if($_POST){
                # 查询该帐号是否是店铺超级管理员，如果不是管理员，无法管理角色。保证role表不出现无限分层的情况
                $condition1['id'] = session('re_admin_id');
                $isMian = M('restaurant_manager')->where($condition1)->getField('is_main');
                if($isMian === 1){
                    $msg['code'] = 10003;
                    $msg['msg'] = "删除失败，请联系店铺管理员进行删除！";
                    exit(json_encode($msg));
                }

                $condition['id']   =   trim(I('post.id'));
                $delRes = M('restaurant_manager')->where($condition)->delete();
                if($delRes){
                    $where['restaurant_id']   =   session('restaurant_id');
                    $count =  M('restaurant_manager')->where($where)->count();
                    $pageNum = 13;
                    $page = ceil($count/$pageNum);
                    $msg['code'] = 10000;
                    $msg['msg'] = "删除成功！";
                    $msg['page'] = $page;
                    exit(json_encode($msg));
                }else{
                    $msg['code'] = 10002;
                    $msg['msg'] = "删除失败！";
                    exit(json_encode($msg));
                }

            }
        }

		//收银员主页
		public function index(){
			$cashier = D('cashier');
			$where['restaurant_id'] = session('restaurant_id');
			$cashierArr = $cashier->where($where)->select();
			$this->assign("cashierArr",$cashierArr);
			$this->display();
		}

		//收银员主页操作后的ajax页
		public function AccountAjax(){
			$cashier = D('cashier');
			$where['restaurant_id'] = session('restaurant_id');
			$cashierArr = $cashier->where($where)->select();
			$this->assign("cashierArr",$cashierArr);
			$this->display('ajaxIndex');
		}


	//添加收银员
	public function Accountsadd(){
		$cashier = D('cashier');
		//帐号校验
		$data['cashier_name'] = $_POST['Cashier_name'];
		$data['cashier_pwd'] = $_POST['Cashier_pwd'];
		$data['cashier_phone'] = $_POST['Cashier_phone'];
		$data['cashier_sex'] = $_POST['Cashier_sex'];
		$data['restaurant_id'] = session('restaurant_id');
		$r = $cashier->add($data);
		if($r){
			$this->AccountAjax();
		}
	}

	//删除
	public function Accountsdel(){
		$cashier = D('cashier');
		$where['cashier_id'] = I('post.Cashier_id');
		$r = $cashier->where($where)->delete();
		if($r){
			$this->AccountAjax();
		}
	}

	//编辑前填充
	public function Accountsmodify(){
		$cashier = D('cashier');
		$where['cashier_id'] = I('post.Cashier_id');
		$arr = $cashier->where($where)->find();
		exit(json_encode($arr));
	}

	//编辑
	public function Accountsupdata(){
		$cashier = D('cashier');
		$data['cashier_id'] = $_POST['Cashier_id'];
		$data['cashier_name'] = $_POST['Cashier_name'];
		$data['cashier_pwd'] = $_POST['Cashier_pwd'];
		//$data['cashier_phone'] = $_POST['Cashier_phone'];
		$data['cashier_sex'] = $_POST['Cashier_sex'];
		$data['restaurant_id'] = session('restaurant_id');
		$r = $cashier->save($data);
		if($r){
			$this->AccountAjax();
		}else{
			$this->AccountAjax();
		}
	}

	//模糊查询
	public function selectBykey(){
		$cashier = D('cashier');
		$condition['restaurant_id'] = session('restaurant_id');
		$condition['cashier_name'] = array('like',"%".$_POST['key']."%");
		$cashierArr = $cashier->where($condition)->select();
		$this->assign("cashierArr",$cashierArr);
		$this->display('ajaxIndex');
	}

		/*//分页
		public function deskInfo(){
			$cashier = D('cashier');
			$pp = I("get.page");
//			dump($pp);
			$condition['restaurant_id'] = session('restaurant_id');
			$p = I("get.page") ? I("get.page") : 1;
			$count = $cashier->count();
			$page = new \Think\PageAjax($count,5);
			$cashierinfo = $cashier->where($condition)->page($p,5)->select();
			$this->assign('info',$cashierinfo);
			$page2  = $page->show();
			$this->assign('page',$page2);
			if($pp == ""){
				$this->display('index');
			}else{
				$this->display('ajaxIndex');
			}
		}*/
	}
?>