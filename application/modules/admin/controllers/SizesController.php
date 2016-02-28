<?php

class SizesController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Images Sizes'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => array_key_exists('sizes', JO_Registry::get('configallowcontrollers')),
			'permision_key' => 'system',
			'sort_order' => 80301
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
		Model_Sizes::initDB();
	}
	
	public function indexAction() {
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
		
    	$percents = Model_Sizes::getAll();
		$this->view->percents = array();
		if($percents) {
			foreach($percents AS $percent) {
				$this->view->percents[] = array(
					'id' => $percent['id'],
					'size' => round($percent['size'], 2),
					'name' => $percent['name']
				);
			}
		}
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $page_id) {
			if($page_id) {
				Model_Sizes::changeSortOrder($page_id, $sort_order);
			}
		}
		
		echo 1;
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Sizes::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/sizes/');
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Sizes::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/sizes/');
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Sizes::delete($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Sizes::get($id);
		}
		
		if($request->getPost('size')) {
    		$this->view->size = $request->getPost('size');
    	} elseif(isset($info)) {
    		$this->view->size = round($info['size'],2);
    	} else {
    		$this->view->size = '';
    	}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
	}

}

?>