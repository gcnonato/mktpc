<?php

class PercentsController extends JO_Action {
	
	public static function config() {
		$is_singlesignon = false;
		if(JO_Registry::get('singlesignon_db_users') && JO_Registry::get('singlesignon_db_users') != JO_Db::getDefaultAdapter()->getConfig('dbname')) {
			$is_singlesignon = true;	
		}
		return array(
			'name' => self::translate('Percentages'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80200,
			'is_singlesignon' => $is_singlesignon
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
		
    	$percents = Model_Percents::getAll();
		$this->view->percents = array();
		if($percents) {
			foreach($percents AS $percent) {
				$this->view->percents[] = array(
					'id' => $percent['id'],
					'percent' => $percent['percent'],
					'from' => WM_Currency::format($percent['from']),
					'to' => WM_Currency::format($percent['to'])
				);
			}
		}
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Percents::createPercent($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/percents/');
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Percents::editePercent($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/percents/');
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Percents::deletePercent($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$module = new Model_Percents;
		
		if($id) {
			$info = $module->getPercent($id);
		}
		
		if($request->getPost('percent')) {
    		$this->view->percent = $request->getPost('percent');
    	} elseif(isset($info)) {
    		$this->view->percent = $info['percent'];
    	} else {
    		$this->view->percent = '';
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
		
	}
	
	

}

?>