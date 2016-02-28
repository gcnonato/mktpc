<?php

class ContactsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Contacts'),
			'has_permision' => true,
			'menu' => self::translate('Contacts'),
			'in_menu' => true,
			'permision_key' => 'contacts',
			'sort_order' => 51000
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
    	
		$this->view->contacts = array();
        $contacts = Model_Contacts::getContacts($data);
        
        if($contacts) {
        	foreach($contacts AS $contact) {
        		$contact['datetime'] = JO_Date::getInstance($contact['datetime'], 'dd MM yy', true)->toString();
        		$contact['has_response'] = $contact['answer_datetime'] != '0000-00-00 00:00:00';
        		if($contact['answer_datetime'] != '0000-00-00 00:00:00') {
        			$contact['answer_datetime'] = JO_Date::getInstance($contact['answer_datetime'], 'dd MM yy', true)->toString();
        		} else {
        			$contact['answer_datetime'] = '';
        		}
				$this->view->contacts[] = $contact;
        	}
        } 
        
        $total_records = Model_Contacts::getTotalContacts($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/contacts/?page={page}');
		$this->view->pagination = $pagination->render();
        
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		$res = Model_Contacts::sendContact($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		if($res) {
    			$this->session->set('successfu_edite', true);
    		}
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/contacts/' . $url);
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Contacts::deleteContact($this->getRequest()->getPost('id'));
	}

	private function getForm() {
		
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$info = Model_Contacts::getContact($id);
		if(!$info) {
			$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/contacts/' . $url);
		}
		
		$this->view->page_num = $this->getRequest()->getRequest('page', 1);
		
		$this->view->info = $info;
		
		if($request->isPost()) {
			$this->view->answer = $request->getPost('answer');
		} else {
			$this->view->answer = $info['answer'];
		}
		
	}
	
}

?>