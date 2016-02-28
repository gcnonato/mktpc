<?php

class MembershipController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Membership'),
			'has_permision' => true,
			'menu' => self::translate('Modules'),
			'in_menu' => true,
			'permision_key' => 'modules',
			'sort_order' => 74000
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
		
		$payments = Model_Membership::getAll();

		$this->view->payments = array();
		if($payments) {
			foreach($payments AS $payment) {
				$payment['price'] = WM_Currency::format($payment['price']);
				$this->view->payments[] = $payment;
			}
		}
	}
	
	public function createAction() {
		$this->setViewChange('form');
		$request = $this->getRequest();
		
		if($request->isPost()) {
    		Model_Membership::create($request->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($request->getBaseUrl() . $request->getModule() . '/membership/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		$request = $this->getRequest();
		
		if($request->isPost()) {
    		Model_Membership::edit($request->getQuery('id'), $request->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($request->getBaseUrl() . $request->getModule() . '/membership/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Membership::delete($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Membership::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $id) {
			Model_Membership::changeSortOrder($id, $sort_order);
		}
		
		echo 1;
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Membership::get($id);
		}
		
		$this->view->languages = array();
		$this->view->def_lang = false;
    	$languages = Model_Language::getLanguages();
    	if($languages) {
    		$this->view->languages = $languages;
    		foreach($languages AS $language) {
    			if($language['language_id'] == JO_Registry::get('default_config_language_id')) {
    				$this->view->def_lang = $language['code'];
    			}
    		}
    	}
		
		if($request->getPost('status')) {
    		$this->view->status = $request->getPost('status');
    	} elseif(isset($info)) {
    		$this->view->status = $info[0]['status'];
    	} else {
    		$this->view->status = 'false';
    	}
		
		if($request->getPost('price')) {
    		$this->view->price = $request->getPost('price');
    	} elseif(isset($info)) {
    		$this->view->price = $info[0]['price'];
    	} else {
    		$this->view->price = 0;
    	}
		
		if($request->getPost('max_items_cnt')) {
    		$this->view->max_items_cnt = $request->getPost('max_items_cnt');
    	} elseif(isset($info)) {
    		$this->view->max_items_cnt = $info[0]['max_items_cnt'];
    	} else {
    		$this->view->max_items_cnt = 0;
    	}
		
		if($request->getPost('order_index')) {
    		$this->view->order_index = $request->getPost('order_index');
    	} elseif(isset($info)) {
    		$this->view->order_index = $info[0]['order_index'];
    	} else {
    		$this->view->order_index = Model_Membership::getMaxPosition();
    	}
		
		if($request->getPost('description')) {
    		$this->view->description = $request->getPost('description');
    	} elseif(isset($info)) {
    		$this->view->description = array();
    		foreach($info as $i) {
    			$this->view->description[$i['lid']] = $i['description'];
    		}
    	} else {
    		$this->view->description = '';
    	}
	}
}

?>