<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class OrderController extends AdminbaseController{
    
	protected $order_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->order_model = M("GoodOrder");
	}
	public function index(){
		$count=$this->order_model->count();
		$page = $this->page($count, 20);
        $orders = $this->order_model
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
		$this->assign("orders",$orders);
		$this->display();
	}

	public function sendGood() {
		$data =array();
		if (isset($_GET['id'])&&!empty($_GET['id'])) {
			$id = I("get.id",0,'intval');
			$data   =  $this->order_model->find($id);
		}
		if (!empty($data)) {
			$data['status'] = $data['status']==1?0:1;
			if ($this->order_model->create($data)!==false) {
					if ($this->order_model->save()!==false) {
						$this->success("操作成功！", U("Order/index"));
					} else {
						$this->error("操作失败！");
					}
				
			} else {
				$this->error($this->order_model->getError());
			}
		}else{
			$this->success("该订单不存在", U("Order/index"));
		}		
	}
	// 删除导航分类
	public function delete(){
		$id = I("get.id",0,'intval');
		if ($this->order_model->where(array('id'=>$id))->delete()!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
}