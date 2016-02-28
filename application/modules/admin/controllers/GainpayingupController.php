<?php

class GainpayingupController extends JO_Action {
	
	public static function config() {
		$is_singlesignon = false;
		if(JO_Registry::get('singlesignon_db_users') && JO_Registry::get('singlesignon_db_users') != JO_Db::getDefaultAdapter()->getConfig('dbname')) {
			$is_singlesignon = true;	
		}
		return array(
			'name' => self::translate('Withdrawals'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'users',
			'sort_order' => 23000,
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
    	
    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
    	
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit')
    	);
        
		$this->view->withdraws = array();
        $withdraws = Model_Users::getWithdraws($data);
        
        if($withdraws) {
        	foreach($withdraws AS $withdraw) {
        		$withdraw['earning'] = WM_Currency::format($withdraw['earning']);
        		$date = new JO_Date($withdraw['datetime'], 'dd MM yy');
        		$withdraw['datetime'] = $date->toString();
        		if($withdraw['paid'] == 'true') {
	        		$date = new JO_Date($withdraw['paid_datetime'], 'dd MM yy');
	        		$withdraw['paid_datetime'] = $date->toString();
        		} else {
        			$withdraw['paid_datetime'] = '';
        		}
        		$withdraw['amount'] = WM_Currency::format($withdraw['amount']);
            	$this->view->withdraws[] = $withdraw;
        	}
        } 
        
        $total_records = Model_Users::getTotalWithdraws($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/gainpayingup/?page={page}');
		$this->view->pagination = $pagination->render();
        
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		$res = Model_Users::editeWithdraw($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		if($res) {
    			$this->session->set('successfu_edite', true);
    		}
    		$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/gainpayingup/' . $url);
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->noViewRenderer(true);
		$res = Model_Users::deleteWithdraw($this->getRequest()->getPost('id'));
		echo $res === false ? '0' : '1';
	}
	
	private function getForm() {
		
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		$info = Model_Users::getWithdraw($id);
		
		if(!$info) {
			$url = '';
    		if($this->getRequest()->getQuery('page')) {
    			$url = '?page=' . $this->getRequest()->getQuery('page');
    		}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/gainpayingup/' . $url);
		}
		
		$this->view->page_num = $this->getRequest()->getRequest('page', 1);
		
		$info['earning_formated'] = WM_Currency::format($info['earning']);
		$info['earning'] = WM_Currency::format($info['earning'], false);
        $date = new JO_Date($info['datetime'], 'dd MM yy');
        $info['datetime'] = $date->toString();
		
		$this->view->info = $info;
		
	}

}

?>