<?php

class Extensions_TopbannerController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Google Analytics PRO'),
			'has_permision' => true,
			'menu' => self::translate('Modules'),
			'in_menu' => false,
			'permision_key' => 'extensions'
		);
	}
	
	public static function info() {
		return array(
			'name' => self::translate('Top banner')
		);
	}
	
	/////////////////// end config

	private $session;
	private $error = array();
	
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
    	
    	$this->view->banners = array();
    	
    	$banners = Model_Extensions_Topbanner::getAll($data);
    	if($banners) {
    		foreach($banners AS $banner) {
    			if($banner['from'] == '0000-00-00') {
    				$banner['from'] = '';
    			} else {
    				$banner['from'] = JO_Date::getInstance($banner['from'], 'dd MM yy', true)->toString();
    			}
    			if($banner['to'] == '0000-00-00') {
    				$banner['to'] = '';
    			} else {
    				$banner['to'] = JO_Date::getInstance($banner['to'], 'dd MM yy', true)->toString();
    			}
    			$this->view->banners[] = $banner;
    		}
    	}
		
    	$total_records = Model_Extensions_Topbanner::getTotal();
    	
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/extensions/edit/?extension=topbanner&page={page}');
		$this->view->pagination = $pagination->render();
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost() && $this->validateForm()) {
    		Model_Extensions_Topbanner::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '&page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/extensions/edit/?extension=topbanner' . $url);
    	}
		$this->getPageForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost() && $this->validateForm()) {
    		Model_Extensions_Topbanner::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '&page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/extensions/edit/?extension=topbanner' . $url);
    	}
		$this->getPageForm();
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Extensions_Topbanner::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Extensions_Topbanner::delete($this->getRequest()->getPost('id'));
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	
	private function getPageForm() {
		$request = $this->getRequest();
		
		$page_id = $request->getQuery('id');
		
		$pages_module = new Model_Extensions_Topbanner();
		
		$this->view->page_num = $this->getRequest()->getRequest('page', 1);
		
		$this->view->cancle = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/extensions/edit/?extension=topbanner&page=' . $this->view->page_num;
		
		if($this->error) {
			$this->view->error_warning = implode('; ', $this->error);
		}
		
		if($page_id) {
			$page_info = $pages_module->get($page_id);
		}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($page_info)) {
    		$this->view->name = $page_info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
		if($request->getPost('url')) {
    		$this->view->url = $request->getPost('url');
    	} elseif(isset($page_info)) {
    		$this->view->url = $page_info['url'];
    	} else {
    		$this->view->url = '';
    	}
    	
		if($request->getPost('html')) {
    		$this->view->html = $request->getPost('html');
    	} elseif(isset($page_info)) {
    		$this->view->html = $page_info['html'];
    	} else {
    		$this->view->html = '';
    	}
    	
		if($request->getPost('background')) {
    		$this->view->background = $request->getPost('background');
    	} elseif(isset($page_info)) {
    		$this->view->background = $page_info['background'];
    	} else {
    		$this->view->background = '';
    	}
    	
		if($request->getPost('from')) {
    		$this->view->from = $request->getPost('from');
    	} elseif(isset($page_info)) {
    		if($page_info['from'] == '0000-00-00') {
    			$this->view->from = '';
    		} else {
    			$this->view->from = JO_Date::getInstance($page_info['from'], 'dd.mm.yy', true)->toString();
    		}
    	} else {
    		$this->view->from = '';
    	}
    	
    	if(is_numeric($request->getPost('cookie'))) {
    		$this->view->cookie = $request->getPost('cookie');
    	} elseif(isset($page_info)) {
    			$this->view->cookie = $page_info['cookie'];
  
    	} else {
    		$this->view->cookie = '';
    	}
    	
		if($request->getPost('to')) {
    		$this->view->to = $request->getPost('to');
    	} elseif(isset($page_info)) {
    		if($page_info['to'] == '0000-00-00') {
    			$this->view->to = '';
    		} else {
    			$this->view->to = JO_Date::getInstance($page_info['to'], 'dd.mm.yy', true)->toString();
    		}
    	} else {
    		$this->view->to = '';
    	}
    	
		if($request->getPost('close')) {
    		$this->view->close = $request->getPost('close');
    	} elseif(isset($page_info)) {
    		$this->view->close = $page_info['close'];
    	} else {
    		$this->view->close = 'true';
    	}
    	
		if($request->getPost('width')) {
    		$this->view->width = $request->getPost('width');
    	} elseif(isset($page_info)) {
    		$this->view->width = $page_info['width'];
    	} else {
    		$this->view->width = 976;
    	}
    	
		if($request->getPost('height')) {
    		$this->view->height = $request->getPost('height');
    	} elseif(isset($page_info)) {
    		$this->view->height = $page_info['height'];
    	} else {
    		$this->view->height = 50;
    	}
	
    	
		/////// logo
		$image_model = new Model_Images;
    	
		if($request->getPost('photo')) {
    		$this->view->photo = $request->getPost('photo');
    	} elseif(isset($page_info)) {
    		$this->view->photo = $page_info['photo'];
    	} else {
    		$this->view->photo = '';
    	}
    	
    	if($this->view->photo) {
    		$this->view->preview = $image_model->resize($this->view->photo, 100, 100);
    	} else {
    		$this->view->preview = $image_model->resize(JO_Registry::forceGet('no_image'), 100, 100);
    	}
    	
    	if(!$this->view->preview) {
    		$this->view->preview = $image_model->resize(JO_Registry::forceGet('no_image'), 100, 100);
    	}
    	
    	if(!$this->view->preview) {
    		$this->view->preview = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
    	$this->view->preview_no_image = $image_model->resize(JO_Registry::forceGet('no_image'), 100, 100);
    	
    	if(!$this->view->preview_no_image) {
    		$this->view->preview_no_image = $image_model->resize('/no_image.png', 100, 100);
    	}
	}

	private function validateForm() {
		$request = $this->getRequest();
		
		$html = trim(strip_tags(html_entity_decode($request->getPost('html'), ENT_QUOTES, 'utf-8')));
		
	
		if(!trim($request->getPost('name'))) {
			$this->error['name'] = $this->translate('Please enter a name');
		}
		
		if(!trim($request->getPost('url')) && !$html) {
			$this->error['url'] = $this->translate('Please enter url and photo or html');
		} elseif(trim($request->getPost('url')) && !trim($request->getPost('photo')) && !$html) {
			$this->error['url'] = $this->translate('Please enter url and photo or html');
		} elseif(!trim($request->getPost('url')) && trim($request->getPost('photo')) && !$html) {
			$this->error['url'] = $this->translate('Please enter url and photo or html');
		}
		
		if(trim($request->getPost('url')) && !JO_Validate::validateHost(trim($request->getPost('url')))) {
			$this->error['url1'] = $this->translate('Please enter valid url');
		}
		
		if($this->error) {
			return false;
		} else {
			return true;
		}
	}
	
}

?>