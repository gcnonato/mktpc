<?php

class AttributesController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Attributes'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80900
		);
	}
	
	private static function typeAttributes($type = null) {
		$arr = array(
			'select' => self::translate('Select box'),
			'check' => self::translate('Checkbox'),
			'radio' => self::translate('Radio'),
			'input' => self::translate('Input'),
		);
		
		if($type) {
			return isset($arr[$type]) ? $arr[$type] : null;
		}
		
		return $arr;
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
    	
    	$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
    	
    	if($sub_of) {
    		$category_info = Model_Attributes::getAttribute2($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/attributes/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/attributes/';
    	}
    	
    	$data = array(
    		'filter_sub_of' => $sub_of
    	);
    	
		$this->view->pages = array();
        $categories = Model_Attributes::getAttributes($data);
        
        if($categories) {
            
        	$url = '';
        	if($sub_of) {
        		$url .= "&sub_of=" . $sub_of;
        	}
        	
            foreach($categories AS $category) {
            	if(!$sub_of) {
            		$category['type_text'] = self::typeAttributes($category['type']);
            	}
                $this->view->pages[] = $category;
            }
        } 
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Attributes::createAttribute($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/attributes/' . $url);
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Attributes::editeAttribute($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/attributes/' . $url);
    	}
		$this->getForm();
	}
	
	public function sort_orderAction() {
		$this->noViewRenderer(true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $id) {
			if($id) {
				Model_Attributes::changeSortOrder($id, $sort_order);
			}
		}
		echo 1;
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Attributes::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function changeRequiredAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Attributes::changeRequired($this->getRequest()->getPost('id'));
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Attributes::deleteAttribute($this->getRequest()->getPost('id'));
	}
	
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$module = new Model_Attributes();
		
		$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
    	
		if($sub_of) {
    		$category_info = Model_Attributes::getAttribute2($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/attributes/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/attributes/';
    	}
		
		if($id) {
			$info = $module->getAttribute($id);
		}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
		if($request->getPost('search')) {
    		$this->view->search = $request->getPost('search');
    	} elseif(isset($info)) {
    		$this->view->search = $info['search'];
    	} else {
    		$this->view->search = 'false';
    	}
		
		if($request->getPost('visible')) {
    		$this->view->visible = $request->getPost('visible');
    	} elseif(isset($info)) {
    		$this->view->visible = $info['visible'];
    	} else {
    		$this->view->visible = 'true';
    	}
		
    	if($sub_of) {
	    	
	    	$this->view->categories = Model_Attributes::getAttributes();
		
			if($request->getPost('category_id')) {
	    		$this->view->category_id = $request->getPost('category_id');
	    	} elseif(isset($info)) {
	    		$this->view->category_id = $info['category_id'];
	    	} else {
	    		$this->view->category_id = '';
	    	}
    	
	    	if(isset($info)) {
	    		$this->view->photo = $info['photo'];
	    	} else {
	    		$this->view->photo = '';
	    	}
    		
    	} else {
    		
    		$this->view->categories = Model_Categories::getCategories(array(
    			'filter_sub_of' => 0
    		));
    		
    		$this->view->types = self::typeAttributes();

	    	if($request->getPost('categories')) {
	    		$this->view->categories_s = $request->getPost('categories');
	    	} elseif(isset($info)) {
	    		$this->view->categories_s = explode(',', $info['categories']);
	    	} else {
	    		$this->view->categories_s = array();
	    	}
		
			if($request->getPost('type')) {
	    		$this->view->type = $request->getPost('type');
	    	} elseif(isset($info)) {
	    		$this->view->type = $info['type'];
	    	} else {
	    		$this->view->type = '';
	    	}
	    	
    	}
		

	}
	
	
}

?>