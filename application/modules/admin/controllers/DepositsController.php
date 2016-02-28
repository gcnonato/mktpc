<?php

class DepositsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Deposits'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80300
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
		
    	$percents = Model_Deposit::getAll();
		$this->view->percents = array();
		if($percents) {
			foreach($percents AS $percent) {
				$this->view->percents[] = array(
					'id' => $percent['id'],
					'deposit' => WM_Currency::format($percent['deposit'])
				);
			}
		}
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Deposit::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/deposits/');
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Deposit::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/deposits/');
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Deposit::delete($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Deposit::get($id);
		}
		
		if($request->getPost('deposit')) {
    		$this->view->deposit = $request->getPost('deposit');
    	} elseif(isset($info)) {
    		$this->view->deposit = $info['deposit'];
    	} else {
    		$this->view->deposit = '';
    	}
		
	}

}

?>