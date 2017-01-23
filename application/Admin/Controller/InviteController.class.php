<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class InviteController extends AdminbaseController{
    
	protected $InviteCode;
	
	public function _initialize() {
		parent::_initialize();
		$this->InviteCode = M("InviteCode");
	}
	public function index(){
		$where = array();
		$count=$this->InviteCode->where($where)->count();
		$page = $this->page($count, 20);
        $invites = $this->InviteCode
            ->where($where)
            ->order("status DESC")
            ->order("add_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
	    foreach ($invites as $key => &$value) {
	    	if ($value['userid']!=0) {
	    		$user = M('Users')->find($value['userid']);
	    		$value['username'] = $user['user_nicename'];
	    	}
	    }
		$this->assign("page",$page->show('Admin'));
		$this->assign("invites",$invites);
		$this->display();
	}

	public function addCode() {
		$data = array(
			'code'=>md5('invite_code_'.$this->InviteCode->count()),
			'userid'=>0,
			'status'=>1,
			'add_time'=>time(),
			);
		if ($this->InviteCode->create($data)!==false) {
			$id = $this->InviteCode->add();
			if ($id!==false) {
				$this->success("添加成功！", U("Invite/index"));
			} else {
				$this->error("添加失败！");
			}
		} else {
			$this->error($this->InviteCode->getError());
		}		
	}

	// 删除导航分类
	public function delete(){
		$id = I("get.id",0,'intval');
		if ($this->InviteCode->where(array('id'=>$id))->delete()!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
}