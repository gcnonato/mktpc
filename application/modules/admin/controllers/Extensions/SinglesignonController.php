<?php

class Extensions_SinglesignonController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Simple Single Sign-On'),
			'has_permision' => true,
			'menu' => self::translate('Modules'),
			'in_menu' => false,
			'permision_key' => 'extensions'
		);
	}
	
	public static function info() {
		return array(
			'name' => self::translate('Simple Single Sign-On')
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
    	
    	$request = $this->getRequest();
    	
    	if($request->isPost()) {
    		$single_sign_on = $this->getRequest()->getPost('single_sign_on');
    		$results = array();
    		if(is_array($single_sign_on)) {
    			foreach($single_sign_on AS $row => $value) {
    				if($value['url'] && $url = JO_Validate::validateHost($value['url'])) {
    					$value['url'] = str_replace('www.', '', $url);
    					$results[] = $value;
    				}
    			}
    		}
    		Model_Settings::updateAll(array(
    			'single_sign_on' => $results,
    			'singlesignon' => $request->getPost('singlesignon')
    		));
    		
    		if(file_exists(BASE_PATH . '/cache/extensions/singlesignon/')) {
    			self::unlink(BASE_PATH . '/cache/extensions/singlesignon/');
    		}
    		
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/extensions/edit/?extension=singlesignon');
    	}
    	
    	$this->view->dbuser = JO_Db::getDefaultAdapter()->getConfig('username');
    	
    	$image_model = new Model_Images;
    	
    	$this->view->single_sign_on = array();
    	$single_sign_on = Model_Settings::getSettingsPairs(array(
    		'filter_group' => 'single_sign_on'
    	));
    	
    	$sort_order = array();
    	foreach($single_sign_on AS $row => $data) {
    		$sort_order[$row] = isset($data['sort_order']) ? $data['sort_order'] : 0;
    		$data['preview'] = $image_model->resize($data['site_logo'], 100, 100);
    		$this->view->single_sign_on[$row] = $data;
    	}

    	array_multisort($sort_order, SORT_ASC, $this->view->single_sign_on);
    	
    	$this->view->preview = $image_model->resize(JO_Registry::get('no_image'), 100, 100);
    	
    	if(!$this->view->preview) {
    		$this->view->preview = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
    	
    	if($request->isPost()) {
    		$this->view->singlesignon_status = $request->getPost('singlesignon[singlesignon_status]');
    	} else {
    		$this->view->singlesignon_status = JO_Registry::get('singlesignon_status');
    	}
    	
    	if($request->isPost()) {
    		$this->view->singlesignon_enable_login = $request->getPost('singlesignon[singlesignon_enable_login]');
    	} else {
    		$this->view->singlesignon_enable_login = JO_Registry::get('singlesignon_enable_login');
    	}
    	
    	if($request->isPost()) {
    		$this->view->singlesignon_enable_dropdown = $request->getPost('singlesignon[singlesignon_enable_dropdown]');
    	} else {
    		$this->view->singlesignon_enable_dropdown = JO_Registry::get('singlesignon_enable_dropdown');
    	}
    	
    	if($request->isPost()) {
    		$this->view->singlesignon_db_users = $request->getPost('singlesignon[singlesignon_db_users]');
    	} else {
    		$this->view->singlesignon_db_users = JO_Registry::get('singlesignon_db_users');
    	}
    	
	}
	
	public function uninstall() {
		$single_sign_on = Model_Settings::getSettingsPairs(array(
    		'filter_group' => 'singlesignon'
    	));
    	$single_sign_on['singlesignon_status'] = 0;
    	Model_Settings::updateAll(array(
    		'singlesignon' => $single_sign_on
    	));
	}
	
	public static function unlink($dir, $deleteRootToo=true) {
		$dir = rtrim($dir, '/');
	    if(!$dh = @opendir($dir)) {
	        return;
	    }
    	while (false !== ($obj = readdir($dh))) {
	        if($obj == '.' || $obj == '..') {
	            continue;
	        }
	        if (!@unlink($dir . '/' . $obj)) {
	            self::unlink($dir.'/'.$obj, true);
	        }
    	}
    	closedir($dh);
	    if ($deleteRootToo) {
	        @rmdir($dir);
	    }

	    return;
	}

}

?>