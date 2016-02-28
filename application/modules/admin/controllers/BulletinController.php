<?php

class BulletinController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Bulletin'),
			'has_permision' => true,
			'menu' => self::translate('Contacts'),
			'in_menu' => true,
			'permision_key' => 'contacts',
			'sort_order' => 52000
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
    	
        $this->view->bulletines = Model_Bulletin::getBulletines($data);
        
        $total_records = Model_Bulletin::getTotalBulletines($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/bulletin/?page={page}');
		$this->view->pagination = $pagination->render();
        
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Bulletin::createBulletin($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/bulletin/' . $url);
    	}
		$this->getForm();
	}
	
	public function viewAction() {
		$this->setViewChange('view');
		$this->getForm();
	}
	
	private function getForm() {
		
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Bulletin::getBulletin($id);
		}
		
		$this->view->page_num = $this->getRequest()->getRequest('page', 1);
		
		if(isset($info)) {
			$this->view->info = $info;
			$this->view->info['text'] = html_entity_decode($info['text'], ENT_QUOTES, 'utf-8');
			$date = new JO_Date($info['datetime'], 'H:i dd MM yy');
			$this->view->info['datetime'] = $date->toString();
		}
		
		if($id) {
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
		}
		
	}

}

?>