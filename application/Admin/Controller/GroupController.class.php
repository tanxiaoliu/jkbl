<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class GroupController extends AdminbaseController{
    
	protected $group_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->group_model = M("Group");
	}
	public function index(){
		$where = array();
		$count=$this->group_model->count();
		$page = $this->page($count, 20);
        $group = $this->group_model
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($group as $key => &$value) {
        	$value['members'] = M('Users')->where(array('group'=>$value['id']))->order("create_time DESC")->getField('user_nicename');
        	$value['coin'] = M('Users')->where(array('group'=>$value['id']))->sum('score');
        	if (!empty($value['members'])&&is_array(($value['members']))) {
        		$value['members'] = implode(',', $value['members']);
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
        	$value['group'] = $this->group_model->find($value['group']);
        	$value['group'] = $value['group']['name'];
        }
		$this->assign("page",$page->show('Admin'));
		$this->assign("id",1);
		$this->assign("group",$group);
		$this->assign("users",$users);
		$this->display();
	}
	// 物品添加或编辑
	public function add() {
		$data =array();
		if(!empty($_POST)){
			$data=I("post.");
			$data['coin'] = 0;
			$data['add_time'] = time();
			if ($this->group_model->create($data)!==false) {
				if (isset($data['id'])) {
					if ($this->group_model->save()!==false) {
						$this->success("保存成功！", U("group/index"));
					} else {
						$this->error("保存失败！");
					}
				}else{
					if ($this->group_model->add()!==false) {
						$this->success("添加成功！", U("group/index"));
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
		        $data['members'] = M('Users')->where(array('group'=>$data['id']))->order("create_time DESC")->select();
			}
			if (!empty($data)) {
				$this->assign('data',$data);
				$this->assign('members',$data['members']);
		    	$this->display(":group/edit");
			}
			else{
				$this->display();
			}
		}
		
	}

	// 后台评论审核
	public function joinGroup(){
		if(isset($_POST['ids']) && $_GET["joinGroup"]){
			$data["group"]=$_POST['groupid'];
			$ids=join(",",$_POST['ids']);
			if ( M('Users')->where("id in ($ids)")->save($data)!==false) {
				$this->success("加入成功！");
			} else {
				$this->error("加入失败！");
			}
		}
	}

	// 后台评论审核
	public function cancel(){
		if(isset($_GET['groupid']) && $_GET["groupid"] && isset($_GET['id']) && $_GET["id"]){
			$data["group"] = '';
			$groupid=intval($_GET['groupid']);
			$id=intval($_GET['id']);
			if ( M('Users')->where(array('id'=>$id))->save($data)!==false) {
				$this->redirect(U("group/add",array('id'=>$groupid)));
				// $this->success("移除成功！",U("group/add",array('id'=>$groupid)));
			} else {
				$this->error("移除失败！",U("group/add",array('id'=>$groupid)));
			}
		}
	}

	// 删除导航分类
	public function delete(){
		$id = I("get.id",0,'intval');
		if ($this->group_model->where(array('id'=>$id))->delete()!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
}