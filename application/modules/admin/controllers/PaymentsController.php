<?php

class PaymentsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Pamyment methods'),
			'has_permision' => true,
			'menu' => self::translate('Modules'),
			'in_menu' => true,
			'permision_key' => 'modules',
			'sort_order' => 72000
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

    	$files = glob(dirname(__FILE__) . '/Payments/*.php');
    	
    	$this->view->payments = array();
    	if($files) {
    		foreach($files AS $file) {
    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
    				$key = mb_strtolower($match[1], 'utf-8');
    				$this->view->payments[] = array(
    					'key' => $key,
    					'edit' => $this->getRequest()->getModule() . '/payments_' . $key,
    					'name' => $this->translate($match[1]),
    					'sort' => (int)JO_Registry::forceGet($key . '_sort_order'),
    					'status' => ( JO_Registry::forceGet($key . '_status') ? $this->translate('Enabled') : $this->translate('Disabled') )
    				);
    			}
    		}
    	}
	}
}

?>