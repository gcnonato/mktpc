<?php

class CategoriesController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Categories managements'),
			'has_permision' => true,
			'menu' => self::translate('Items'),
			'in_menu' => true,
			'permision_key' => 'items',
			'sort_order' => 32000
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
        
//    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
    	$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
    	
    	if($sub_of) {
    		$category_info = Model_Categories::getCategoryPath($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/categories/?sub_of=' . $category_info['sub_of'];
    	}
    	
    	$data = array(
    		'filter_sub_of' => $sub_of,
//    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
//			'limit' => JO_Registry::get('admin_limit')
    	);
    	
		$this->view->categories = array();
        $categories = Model_Categories::getCategories($data);
        
        if($categories) {
            
        	$url = '';
        	if($sub_of) {
        		$url .= "&sub_of=" . $sub_of;
        	}
        	
            foreach($categories AS $category) {
            	$category['subcategories'] = $this->getRequest()->getModule() . '/categories/?sub_of=' . $category['id'];
            	$category['edit'] = $this->getRequest()->getModule() . '/categories/edit/?id=' . $category['id'] . $url;
//            	$category['up'] = $this->getRequest()->getModule() . '/categories/sort/?sort=up&sub_of='.$category['sub_of'].'&id=' . $category['id'];
//            	$category['down'] = $this->getRequest()->getModule() . '/categories/sort/?sort=down&sub_of='.$category['sub_of'].'&id=' . $category['id'];
                $this->view->categories[] = $category;
            }
        } 
        
//        $total_records = Model_Categories::getTotalCategories($data);
//		
//		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
//		$this->view->total_rows = $total_records;
//		
//		$url = '';
//		if($this->getRequest()->getRequest('sub_of')) {
//			$url = '&amp;sub_of=' . $this->getRequest()->getRequest('sub_of');
//		}
//		
//		$pagination = new Model_Pagination;
//		$pagination->setLimit(JO_Registry::get('admin_limit'));
//		$pagination->setPage($page);
//		$pagination->setTotal($total_records);
//		$pagination->setUrl($this->getRequest()->getModule() . '/categories/?page={page}' . $url);
//		$this->view->pagination = $pagination->render();
        
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Categories::createCategory($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/' . $url);
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Categories::editeCategory($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/' . $url);
    	}
		$this->getForm();
	}

//	public function sortAction() {
//		
//		$this->noLayout(true)->noViewRenderer(true);
//		
//		$request = $this->getRequest();
//		switch($request->getRequest('sort')) {
//			case 'up':
//				Model_Categories::sortOrder($request->getQuery('id'), $request->getQuery('sub_of'), 'ASC');
//			break;
//			case 'down':
//				Model_Categories::sortOrder($request->getQuery('id'), $request->getQuery('sub_of'), 'DESC');
//			break;
//		}
//		
//		$url = '';
//		if($request->getQuery('sub_of')) {
//			$url .= 'sub_of=' . $request->getQuery('sub_of') . '&';
//		}
//		if($request->getQuery('page')) {
//			$url .= 'page=' . $request->getQuery('page') . '&';
//		}
//		
//		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/?' . ($url ? $url : ''));
//		
//	}

	public function sort_orderAction() {
		$this->noViewRenderer(true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $id) {
			if($id) {
				Model_Categories::changeSortOrder($id, $sort_order);
			}
		}
		echo 1;
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Categories::deleteCategory($this->getRequest()->getPost('id'));
	}
	
	public function deleteMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $id) {
				Model_Categories::deleteCategory($id);
			}
		}
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Categories::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $id) {
				Model_Categories::changeStatus($id);
			}
		}
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$module = new Model_Categories();
		
		$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
    	
    	if($sub_of) {
    		$category_info = Model_Categories::getCategoryPath($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/categories/?sub_of=' . $category_info['sub_of'];
    		$this->view->parent_module = $category_info['module'];
    	}
		
		if($id) {
			$info = $module->getCategory($id);
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
    		}
    	}
		
		$this->view->modules = WM_Modules::getList(array('update', 'install', 'admin'));
		
		if($request->getPost('meta_title')) {
    		$this->view->meta_title = $request->getPost('meta_title');
    	} elseif(isset($info)) {
    		$this->view->meta_title = $info[0]['meta_title'];
    	} else {
    		$this->view->meta_title = '';
    	}
		
		if($request->getPost('meta_keywords')) {
    		$this->view->meta_keywords = $request->getPost('meta_keywords');
    	} elseif(isset($info)) {
    		$this->view->meta_keywords = $info[0]['meta_keywords'];
    	} else {
    		$this->view->meta_keywords = '';
    	}
		
		if($request->getPost('meta_description')) {
    		$this->view->meta_description = $request->getPost('meta_description');
    	} elseif(isset($info)) {
    		$this->view->meta_description = $info[0]['meta_description'];
    	} else {
    		$this->view->meta_description = '';
    	}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = array();
    		foreach($info as $i) {
    			$this->view->name[$i['lid']] = $i['name'];
			}
    	} else {
    		$this->view->name = '';
    	}
		/*
		if($request->getPost('text')) {
    		$this->view->text = $request->getPost('text');
    	} elseif(isset($info)) {
    		$this->view->text = $info['text'];
    	} else {
    		$this->view->text = '';
    	}
		*/
		if($request->getPost('visible')) {
    		$this->view->visible = $request->getPost('visible');
    	} elseif(isset($info)) {
    		$this->view->visible = $info[0]['visible'];
    	} else {
    		$this->view->visible = 'true';
    	}
		
		if($request->getPost('default_module')) {
    		$this->view->default_module = $request->getPost('default_module');
    	} elseif(isset($info)) {
    		$this->view->default_module = $info[0]['module'];
    	} else {
    		$this->view->default_module = '';
    	}
		
		
		
	}
	
}

?>