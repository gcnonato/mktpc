<?php

class CountriesController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Countries'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80700
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

    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
    	
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit')
    	);
    	
    	$this->view->countries = array();
    	
    	$countries = Model_Countries::getCountries($data);
    	if($countries) {
    		foreach($countries AS $country) {
    			$this->view->countries[] = $country;
    		}
    	}
		
    	$total_records = Model_Countries::getTotalCountries();
    	
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/countries/?page={page}');
		$this->view->pagination = $pagination->render();
    	
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Countries::createCountry($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/countries/' . $url);
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Countries::editeCountry($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/countries/' . $url);
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->noViewRenderer(true);
		Model_Countries::deleteCountry($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusAction() {
		$this->noViewRenderer(true);
		Model_Countries::changeStatus($this->getRequest()->getPost('id'));
	}
	

	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Countries::getCountry($id);
		}
		
		$this->view->page_num = $this->getRequest()->getRequest('page', 1);
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
		if($request->getPost('visible')) {
    		$this->view->visible = $request->getPost('visible');
    	} elseif(isset($info)) {
    		$this->view->visible = $info['visible'];
    	} else {
    		$this->view->visible = 'true';
    	}
    	
    	if(isset($info)) {
    		$this->view->photo = $info['photo'];
    	} else {
    		$this->view->photo = '';
    	}
		
	}

}

?>