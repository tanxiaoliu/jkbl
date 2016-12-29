<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class GroupsController extends AdminbaseController{
    
	protected $group_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->group_model = M("Group");
	}
	public function index(){
		$where = array();
		$count=$this->group_model->count() ;
		$page = $this->page($count, 20);
        $group = $this->group_model
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($group as $key => &$value) {
        	$members = M('Users')->where(array('groupid'=>$value['id']))->order("create_time DESC")->select();
        	$value['coin'] = M('Users')->where(array('groupid'=>$value['id']))->sum('score');
        	$value['members'] = '';
        	if (!empty($members)&&is_array(($members))) {
        		foreach ($members as  $val) {
	        		$value['members'] .= $val['user_nicename'].',';
	        	}
	        	$value['members'] = rtrim($value['members'],',');
        	}
        }
		$this->assign("page",$page->show('Admin'));
		$this->assign("group",$group);
		$this->display();
	}
	public function members(){
		$where = array();
		$count=M('Users')->count();
		$page = $this->page($count, 20);
        $users = M('Users')
            ->where($where)
            ->order("create_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $group = $this->group_model->select();
        foreach ($users as $key => &$value) {
        	$value['groupid'] = $this->group_model->find($value['groupid']);
        	$value['groupid'] = $value['groupid']['name'];
        }
		$this->assign("page",$page->show('Admin'));
		$this->assign("group",$group);
		$this->assign("users",$users);
		$this->display();
	}
	public function add() {
		$data =array();
		if(!empty($_POST)){
			$data=I("post.");
			$data['coin'] = 0;
			$data['add_time'] = time();
			if ($this->group_model->create($data)!==false) {
				if (isset($data['id'])) {
					if ($this->group_model->save()!==false) {
						$this->_cleanFileCache($data['id']);
						$this->success("保存成功！", U("Groups/index"));
					} else {
						$this->error("保存失败！");
					}
				}else{
					$id = $this->group_model->add();
					if ($id!==false) {
						$this->_cleanFileCache($id);
						$this->success("添加成功！", U("Groups/index"));
					} else {
						$this->error("添加失败！");
					}
				}
			} else {
				$this->error($this->group_model->getError());
			}
		}else{
			if (isset($_GET['id'])&&!empty($_GET['id'])) {
				$id = I("get.id",0,'intval');
				$data   =  $this->group_model->find($id);
		        $data['members'] = M('Users')->where(array('groupid'=>$data['id']))->order("create_time DESC")->select();
			}
			if (!empty($data)) {
				$this->assign('data',$data);
				$this->assign('members',$data['members']);
		    	$this->display(":Groups/edit");
			}
			else{
				$this->display();
			}
		}
		
	}

	public function joinGroup(){

		if(isset($_POST['ids']) && $_GET["joinGroup"]){
			$data["groupid"]=$_POST['groupid'];
			$ids=join(",",$_POST['ids']);
			if ( M('Users')->where("id in ($ids)")->save($data)!==false) {
				$this->_cleanFileCache($data["groupid"]);
				$this->success("加入成功！");
			} else {
				$this->error("加入失败！");
			}
		}
	}

	public function cancel(){
		if(isset($_GET['groupid']) && $_GET["groupid"] && isset($_GET['id']) && $_GET["id"]){
			$data["groupid"] = '';
			$groupid=intval($_GET['groupid']);
			$id=intval($_GET['id']);
			if ( M('Users')->where(array('id'=>$id))->save($data)!==false) {
				$this->_cleanFileCache($groupid);
				$this->redirect(U("Groups/add",array('id'=>$groupid)));
				// $this->success("移除成功！",U("Groups/add",array('id'=>$groupid)));
			} else {
				$this->error("移除失败！",U("Groups/add",array('id'=>$groupid)));
			}
		}
	}

	public function delete(){
		$id = I("get.id",0,'intval');
		if ($this->group_model->where(array('id'=>$id))->delete()!==false) {
				$this->_cleanFileCache($id);
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	protected function _setCacheNull($key){
        S($key,null);
    }
    protected function _cleanFileCache($data["groupid"]){
        for ($i=0; $i < 5; $i++) {
            $this->_setCacheNull($data["groupid"] . '_member_' . date('Y-m-d:H', time()) . '_' . $type);
        }
    }
}