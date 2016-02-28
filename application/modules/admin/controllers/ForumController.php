<?php
    
class ForumController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Forum'),
			'has_permision' => true,
			'menu' => self::translate('Modules'),
			'in_menu' => true,
			'permision_key' => 'modules',
			'sort_order' => 75000
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
		
		$this->view->threads = Model_Forum::getAll();
		$this->view->comments = Model_Forum::getReported();
	}
	
	public function createAction() {
		$this->setViewChange('form');
		$request = $this->getRequest();
		
		if($request->isPost()) {
    		Model_Forum::create($request->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($request->getBaseUrl() . $request->getModule() . '/forum/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		$request = $this->getRequest();
		
		if($request->isPost()) {
    		Model_Forum::edit($request->getQuery('id'), $request->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($request->getBaseUrl() . $request->getModule() . '/forum/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$id = $this->getRequest()->getPost('id');
		if(is_numeric($id)) {
			Model_Forum::delete($id);
		} elseif(strpos($id, 'comment_') !== false) {
			$id = str_replace('comment_', '', $id);
			Model_Forum::deleteComment($id);
		}
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Forum::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $id) {
			Model_Forum::changeSortOrder($id, $sort_order);
		}
		
		echo 1;
	}
	
	public function checkedAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Forum::changeReport($this->getRequest()->getPost('id'));				
		echo 1;
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Forum::get($id);
		}
		
		if($request->getPost('status')) {
    		$this->view->status = $request->getPost('status');
    	} elseif(isset($info)) {
    		$this->view->status = $info['status'];
    	} else {
    		$this->view->status = 'false';
    	}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
		if($request->getPost('order_index')) {
    		$this->view->order_index = $request->getPost('order_index');
    	} elseif(isset($info)) {
    		$this->view->order_index = $info['order_index'];
    	} else {
    		$this->view->order_index = Model_Forum::getMaxPosition();
    	}
		
		$this->view->comments = Model_Forum::getReported();
	}
}
?>