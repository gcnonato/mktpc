<?php

class PagesController extends JO_Action  {
	
	public static function config() {
		return array(
			'name' => self::translate('Pages management'),
			'has_permision' => true,
			'menu' => self::translate('Pages'),
			'in_menu' => true,
			'permision_key' => 'pages',
			'sort_order' => 41000
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		$pages_module = new Model_Pages();
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}	
    	
    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
		$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
		
		if($sub_of) {
    		$category_info = $pages_module->getPagePath($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/pages/?sub_of=' . $category_info['sub_of'];
    	}
		
		$data = array(
			'filter_sub_of' => $sub_of,
//			'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
//			'limit' => JO_Registry::get('admin_limit')
		);
    
		$this->view->pages = $pages_module->getPages($data);
		
//		$total_records = $pages_module->getTotalPages();
//		
//		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
//		$this->view->total_rows = $total_records;
//		
//		$pagination = new Model_Pagination;
//		$pagination->setLimit(JO_Registry::get('admin_limit'));
//		$pagination->setPage($page);
//		$pagination->setTotal($total_records);
//		$pagination->setUrl($this->getRequest()->getModule() . '/pages/?page={page}');
//		$this->view->pagination = $pagination->render();
    	
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Pages::createPage($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/' . $url);
    	}
		$this->getPageForm();
	}
	
	public function editeAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Pages::editePage($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/' . $url);
    	}
		$this->getPageForm();
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Pages::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Pages::changeStatus($record_id);
			}
		}
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Pages::deletePage($this->getRequest()->getPost('id'));
	}
	
	public function deleteMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Pages::deletePage($record_id);
			}
		}
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $page_id) {
			if($page_id) {
				Model_Pages::changeSortOrder($page_id, $sort_order);
			}
		}
		
		echo 1;
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	
	private function getPageForm() {
		$request = $this->getRequest();
		
		$page_id = $request->getQuery('id');
		
		$pages_module = new Model_Pages();
		
		$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
		
		if($page_id) {
			$page_info = $pages_module->getPage($page_id);
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
				$this->view->name[$language['language_id']] = '';
				$this->view->text[$language['language_id']] = '';
    		}
    	}
		
		if($sub_of) {
    		$category_info = $pages_module->getPagePath($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/pages/?sub_of=' . $category_info['sub_of'];
    	}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($page_info)) {
    		$this->view->name = array();
    		foreach($page_info as $i) {
    			$this->view->name[$i['lid']] = $i['name'];
			}
    	}
    	
		if($request->getPost('text')) {
    		$this->view->text = $request->getPost('text');
    	} elseif(isset($page_info)) {
    		$this->view->text = array();
    		foreach($page_info as $i) {
    			$this->view->text[$i['lid']] = $i['text'];
			}
    	}
    	
		if($request->getPost('visible')) {
    		$this->view->visible = $request->getPost('visible');
    	} elseif(isset($page_info)) {
    		$this->view->visible = $page_info[0]['visible'];
    	} else {
    		$this->view->visible = 'true';
    	}
    	
		if($request->getPost('meta_title')) {
    		$this->view->meta_title = $request->getPost('meta_title');
    	} elseif(isset($page_info)) {
    		$this->view->meta_title = $page_info[0]['meta_title'];
    	} else {
    		$this->view->meta_title = '';
    	}
    	
		if($request->getPost('meta_keywords')) {
    		$this->view->meta_keywords = $request->getPost('meta_keywords');
    	} elseif(isset($page_info)) {
    		$this->view->meta_keywords = $page_info[0]['meta_keywords'];
    	} else {
    		$this->view->meta_keywords = '';
    	}
    	
		if($request->getPost('meta_description')) {
    		$this->view->meta_description = $request->getPost('meta_description');
    	} elseif(isset($page_info)) {
    		$this->view->meta_description = $page_info[0]['meta_description'];
    	} else {
    		$this->view->meta_description = '';
    	}
    	
    	
		if($request->getPost('menu')) {
    		$this->view->menu = $request->getPost('menu');
    	} elseif(isset($page_info)) {
    		$this->view->menu = $page_info[0]['menu'];
    	} else {
    		$this->view->menu = '';
    	}
    	
		if($request->getPost('footer')) {
    		$this->view->footer = $request->getPost('footer');
    	} elseif(isset($page_info)) {
    		$this->view->footer = $page_info[0]['footer'];
    	} else {
    		$this->view->footer = 'false';
    	}
    	
		if($request->getPost('key')) {
    		$this->view->key = $request->getPost('key');
    	} elseif(isset($page_info)) {
    		$this->view->key = $page_info[0]['key'];
    	} else {
    		$this->view->key = '';
    	}
    	
	if($request->getPost('url')) {
    		$this->view->url = $request->getPost('url');
    	} elseif(isset($page_info)) {
    		$this->view->url = $page_info[0]['url'];
    	} else {
    		$this->view->url = '';
    	}
    	
    	
		
	}
	
	
  
}