<?php

class BulletinemailsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Bulletin E-mails'),
			'has_permision' => true,
			'menu' => self::translate('Contacts'),
			'in_menu' => true,
			'permision_key' => 'contacts',
			'sort_order' => 53000
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
        
    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
    	
    	$this->view->filter_username = $request->getQuery('filter_username');
    	$this->view->filter_email = $request->getQuery('filter_email');
    	$this->view->filter_bulletin_subscribe = $request->getQuery('filter_bulletin_subscribe');
    	
    	$url = '';
    	if($this->view->filter_username) {
    		$url .= '&filter_username=' . $this->view->filter_username;
    	}
    	if($this->view->filter_email) {
    		$url .= '&filter_email=' . $this->view->filter_email;
    	}
    	if($this->view->filter_bulletin_subscribe) {
    		$url .= '&filter_bulletin_subscribe=' . $this->view->filter_bulletin_subscribe;
    	}
    	
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit'),
    		'filter_username' => $this->view->filter_username,
    		'filter_email' => $this->view->filter_email,
    		'filter_bulletin_subscribe' => $this->view->filter_bulletin_subscribe
    	); 
    	
        $this->view->categories = Model_Bulletinemails::getEmails($data);
        
        $total_records = Model_Bulletinemails::getTotalEmails($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/categories/?page={page}' . $url);
		$this->view->pagination = $pagination->render();
        
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Bulletinemails::deleteEmail($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Bulletinemails::changeStatus($this->getRequest()->getPost('id'));
	}

}

?>