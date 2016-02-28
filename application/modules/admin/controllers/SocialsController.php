<?php
class SocialsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Social media'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 81000
		);
	}
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction()
	{
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
		
		$this->view->pages = array();
        $socials = Model_Socials::getSocials();

        if($socials) {
           $this->view->pages = $socials;
        }
	}
	
	public function createAction() {
		$this->setViewChange('form');
		$request = $this->getRequest();
		if($request->isPost()) {
  
  			Model_Socials::createSocials($request->getParams());
    		$this->session->set('successfu_edite', true);
  
    		$this->redirect($request->getBaseUrl() . $request->getModule() . '/socials/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		$request = $this->getRequest();
		
		if($request->isPost()) {
    		Model_Socials::editeSocials($request->getRequest('id'), $request->getParams());
			$this->session->set('successfu_edite', true);
			
    		$this->redirect($request->getBaseUrl() . $request->getModule() . '/socials/');
    	}
		
		$this->getForm();
	}
	
	public function sort_orderAction() {
		$this->noViewRenderer(true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $id) {
			if($id) {
				Model_Socials::changeSortOrder($id, $sort_order);
			}
		}
		
		echo 1;
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$request = $this->getRequest();
		
		if($request->issetPost('id')) {
			Model_Socials::changeStatus($request->getPost('id'));
		}
		
		echo 1;
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$request = $this->getRequest();
		
		if($request->issetPost('id')) {
			Model_Socials::deleteSocials($request->getPost('id'));
		}
		
		echo 1;
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$module = new Model_Socials();
		
		if($id) {
			$info = $module->getSocial($id);
		}
		
		if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
			$this->view->link = $request->getPost('link');
    	} elseif(isset($info)) {
    		$this->view->name = $info['name'];
			$this->view->link = $info['link'];
			$this->view->photo = $info['photo'];
    	} else {
    		$this->view->name = '';
			$this->view->link = '';
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