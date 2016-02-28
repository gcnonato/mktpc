<?php

class QuizController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Quiz questions'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80800
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
    	
    	$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
    	
    	if($sub_of) {
    		$category_info = Model_Quiz::getQuiz2($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/quiz/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/quiz/';
    	}
    	
    	$data = array(
    		'filter_sub_of' => $sub_of
    	);
    	
		$this->view->pages = array();
        $categories = Model_Quiz::getQuizes($data);
        
        if($categories) {        	
            foreach($categories AS $category) {
                $this->view->pages[] = $category;
            }
        } 
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Quiz::createQuiz($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/quiz/' . $url);
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Quiz::editeQuiz($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($this->getRequest()->getQuery('sub_of')) {
    			$url = '?sub_of=' . $this->getRequest()->getQuery('sub_of');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/quiz/' . $url);
    	}
		$this->getForm();
	}
	
	public function sort_orderAction() {
		$this->noViewRenderer(true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $id) {
			if($id) {
				Model_Quiz::changeSortOrder($id, $sort_order);
			}
		}
		echo 1;
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Quiz::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Quiz::deleteQuiz($this->getRequest()->getPost('id'));
	}
	
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$module = new Model_Quiz();
		
		$this->view->sub_of = $sub_of = $this->getRequest()->getRequest('sub_of', 0);
    	
		if($sub_of) {
    		$category_info = Model_Quiz::getQuiz2($sub_of);
    		if(!$category_info) {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/quiz/');
    		}
    		$this->view->parent_name = $category_info['name'];
    		$this->view->parent_href = $this->getRequest()->getModule() . '/quiz/';
    	}
		
		if($id) {
			$info = $module->getQuiz($id);
		}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
    	} else {
    		$this->view->name = '';
    	}
		
		if($request->getPost('right')) {
    		$this->view->right = $request->getPost('right');
    	} elseif(isset($info['right'])) {
    		$this->view->right = $info['right'];
    	} else {
    		$this->view->right = '';
    	}
		

	}
	
	
}

?>