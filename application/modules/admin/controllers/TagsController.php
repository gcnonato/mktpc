<?php

class TagsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Tags'),
			'has_permision' => true,
			'menu' => self::translate('Items'),
			'in_menu' => true,
			'permision_key' => 'items',
			'sort_order' => 36000
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
		if($this->session->get('tag_exists')) {
    		$this->view->tag_exists = true;
    		$this->session->clear('tag_exists'); 
    	}

    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
    	$this->view->filter_id = $request->getQuery('filter_id');
    	$this->view->filter_name = $request->getQuery('filter_name');
    	$this->view->filter_visible = $request->getQuery('filter_visible');
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit'),
    	    'filter_id' => $this->view->filter_id,
    		'filter_name' => $this->view->filter_name,
    	    'filter_visible' => $this->view->filter_visible,
    	);
    	
    	$this->view->tags = Model_Tags::getTags($data);
    	
    	$total_records = Model_Tags::getTotalTags();
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		$url = '';
	    if($this->view->filter_id) {
    		$url .= '&filter_id=' . $this->view->filter_id;
    	}
    	if($this->view->filter_name) {
    		$url .= '&filter_name=' . $this->view->filter_name;
    	}
	    if($this->view->filter_visible) {
    		$url .= '&filter_visible=' . $this->view->filter_visible;
    	}
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/tags/?page={page}'.$url);
		$this->view->pagination = $pagination->render();
    	
	}
	
	public function liveSearchAction() {
		$this->noViewRenderer();
		
		$request = $this->getRequest();
		$type = $request->getQuery('filter');
		
		$json = array();
		

				$tags = Model_Tags::getTags(array(
					'start' => 0,
					'limit' => 100,
					'filter_name' => $request->getQuery('term'),
				));
				if($tags) {
					$cache = array();
					foreach($tags AS $tag) {
						if(!isset($cache[$tag['name']])) {
							$json[] = array(
								'id' 	=> $tag['id'],
								'label' => $tag['name'],
								'value' => $tag['name']
							);
							$cache[$tag['name']] = true;
						}
					}
				}
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		
    	echo JO_Json::encode($json);
	}
	
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		$res = Model_Tags::createTag($this->getRequest()->getParams());
    		if($res == -1) {
    			$this->session->set('tag_exists', true);
    		} else {
    			$this->session->set('successfu_edite', true);
    		}
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/tags/' . $url);
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		$res = Model_Tags::editeTag($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
			if($res == -1) {
    			$this->session->set('tag_exists', true);
    		} else {
    			$this->session->set('successfu_edite', true);
    		}
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/tags/' . $url);
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Tags::deleteTag($this->getRequest()->getPost('id'));
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Tags::changeStatus($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Tags::getTag($id);
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
		
	}
	

}

?>