<?php

class BadgesController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Badges'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'users',
			'sort_order' => 25000
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
    	
		$this->view->is_singlesignon = false;
		if(JO_Registry::get('singlesignon_db_users') && JO_Registry::get('singlesignon_db_users') != JO_Db::getDefaultAdapter()->getConfig('dbname')) {
			$this->view->is_singlesignon = true;	
		}

    	$request = $this->getRequest();
    	
    	$filter_type = 'system';
    	if(in_array($request->getQuery('type'), array('system','other','buyers','authors','referrals'))) {
    		$filter_type = $request->getQuery('type');
    	}
    	
    	$this->view->filter_type = $filter_type;
    	
    	$this->view->badges = Model_Badges::getBadges(array(
    		'filter_type' => $filter_type
    	));
    	
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Badges::createBadge($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('type')) {
    			$url = '?type=' . $this->getRequest()->getQuery('type');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/badges/' . $url);
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Badges::editeBadge($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('type')) {
    			$url = '?type=' . $this->getRequest()->getQuery('type');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/badges/' . $url);
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->noViewRenderer(true);
		Model_Badges::deleteBadge($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusAction() {
		$this->noViewRenderer(true);
		Model_Badges::changeStatus($this->getRequest()->getPost('id'));
	}
	

	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Badges::getBadge($id);
		}
		
		if(!in_array($request->getQuery('type'), array('system','other','buyers','authors','referrals'))) {
    		$this->redirect($request->getBaseUrl() . $request->getModule() . '/badges/?type=system');
    	}
		
		$this->view->type = $this->getRequest()->getQuery('type');
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
		if($request->getPost('sys_key')) {
    		$this->view->sys_key = $request->getPost('sys_key');
    	} elseif(isset($info)) {
    		$this->view->sys_key = $info['sys_key'];
    	} else {
    		$this->view->sys_key = '';
    	}
		
		if($request->getPost('from')) {
    		$this->view->from = $request->getPost('from');
    	} elseif(isset($info)) {
    		$this->view->from = $info['from'];
    	} else {
    		$this->view->from = '';
    	}
		
		if($request->getPost('to')) {
    		$this->view->to = $request->getPost('to');
    	} elseif(isset($info)) {
    		$this->view->to = $info['to'];
    	} else {
    		$this->view->to = '';
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
    	
    	$this->view->badges_system = array(
    		'location_global_community' => $this->translate('Location. We\'re a Global Community!'),
    		'has_free_file_month' => $this->translate('Has contributed a Free File of the Month'),
    		'has_been_featured' => $this->translate('Has been featured'),
    		'has_had_item_featured' => $this->translate('Has had an item featured'),
    		'is_exclusive_author' => $this->translate('Is an exclusive author')
    	);
		
	}

}

?>