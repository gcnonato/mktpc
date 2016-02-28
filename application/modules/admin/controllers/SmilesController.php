<?php

class SmilesController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Emot icons'),
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
    	
    	Model_Smiles::initDbInstall();

    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
    	
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit')
    	);
    	
    	$this->view->smiles = array();
    	
    	$smiles = Model_Smiles::getSmiles($data);
    	if($smiles) {
    		foreach($smiles AS $country) {
    			$this->view->smiles[] = $country;
    		}
    	}
		
    	$total_records = Model_Smiles::getTotalSmiles();
    	
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/smiles/?page={page}');
		$this->view->pagination = $pagination->render();
    	
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Smiles::createSmile($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/smiles/' . $url);
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Smiles::editeSmile($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/smiles/' . $url);
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->noViewRenderer(true);
		Model_Smiles::deleteSmile($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusAction() {
		$this->noViewRenderer(true);
		Model_Smiles::changeStatus($this->getRequest()->getPost('id'));
	}
	

	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Smiles::getSmile($id);
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
		
		if($request->getPost('code')) {
    		$this->view->code = $request->getPost('code');
    	} elseif(isset($info)) {
    		$this->view->code = $info['code'];
    	} else {
    		$this->view->code = '';
    	}
    	
    	if(isset($info)) {
    		$this->view->photo = $info['photo'];
    	} else {
    		$this->view->photo = '';
    	}
		
	}

}

?>