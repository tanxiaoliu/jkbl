<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class GoodsController extends AdminbaseController{
    
	protected $goods_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->goods_model = M("Good");
	}
	public function index(){
		$goods = $this->goods_model->select();
		$this->assign("goods",$goods);
		$this->display();
	}
	// 物品添加或编辑
	public function add() {
		$data =array();
		if(!empty($_POST)){
			$data=I("post.");
			$data['nums'] = 0;
			$data['add_time'] = time();
			if ($this->goods_model->create($data)!==false) {
				if (isset($data['id'])) {
					if ($this->goods_model->save()!==false) {
						$this->success("保存成功！", U("goods/index"));
					} else {
						$this->error("保存失败！");
					}
				}else{
					if ($this->goods_model->add()!==false) {
						$this->success("添加成功！", U("goods/index"));
					} else {
						$this->error("添加失败！");
					}
				}
			} else {
				$this->error($this->goods_model->getError());
			}
		}else{
			if (isset($_GET['id'])&&!empty($_GET['id'])) {
				$id = I("get.id",0,'intval');
				$data   =  $this->goods_model->find($id);
			}
			if (!empty($data)) {
				$this->assign('data',$data);
		    	$this->display(":Goods/edit");
			}
			else{
				$this->display();
			}
		}
		
	}
	// 删除导航分类
	public function delete(){
		$id = I("get.id",0,'intval');
		if ($this->goods_model->where(array('id'=>$id))->delete()!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
}