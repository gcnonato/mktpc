<?php

class ContactscategoriesController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Issue categories'),
			'has_permision' => true,
			'menu' => self::translate('Contacts'),
			'in_menu' => true,
			'permision_key' => 'contacts',
			'sort_order' => 54000
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
        
        $this->view->categories = Model_Contactscategories::getCategories();
        
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Contactscategories::createCategory($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/contactscategories/');
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Contactscategories::editeCategory($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/contactscategories/');
    	}
		$this->getForm();
	}

	public function sort_orderAction() {
		$this->noViewRenderer(true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $id) {
			if($id) {
				Model_Contactscategories::changeSortOrder($id, $sort_order);
			}
		}
		echo 1;
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Contactscategories::deleteCategory($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Contactscategories::changeStatus($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$module = new Model_Contactscategories();
		
		if($id) {
			$info = $module->getCategory($id);
		}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
		if($request->getPost('text')) {
    		$this->view->text = $request->getPost('text');
    	} elseif(isset($info)) {
    		$this->view->text = $info['text'];
    	} else {
    		$this->view->text = '';
    	}
		
		if($request->getPost('visible')) {
    		$this->view->visible = $request->getPost('visible');
    	} elseif(isset($info)) {
    		$this->view->visible = $info['visible'];
    	} else {
    		$this->view->visible = 'true';
    	}
		
		
		
	}
	
	

}

?>