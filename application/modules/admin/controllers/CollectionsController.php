<?php

class CollectionsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Collections'),
			'has_permision' => true,
			'menu' => self::translate('Items'),
			'in_menu' => true,
			'permision_key' => 'items',
			'sort_order' => 35000
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
    	
    	$this->view->collections = array();
    	$collections = Model_Collections::getCollections($data);
    	if($collections) {
    		foreach($collections AS $collection) {
    			$collection['href'] = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=collections&action=view&collection_id=' . $collection['id']);
    			$this->view->collections[] = $collection;
    		}
    	}
    	
    	$total_records = Model_Collections::getTotalCollections();
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/collections/?page={page}');
		$this->view->pagination = $pagination->render();
    	
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Collections::deleteCollection($this->getRequest()->getPost('id'));
	}

}

?>