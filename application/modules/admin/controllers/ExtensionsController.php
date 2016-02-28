<?php

class ExtensionsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Extensions'),
			'has_permision' => true,
			'menu' => self::translate('Modules'),
			'in_menu' => true,
			'permision_key' => 'modules',
			'sort_order' => 71000
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
		
		$request = $this->getRequest();
		
		$files = glob(dirname(__FILE__) . '/Extensions/*.php');
    	
    	$this->view->extensions = array();
    	if($files) {
    		foreach($files AS $file) { 
    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
    				$key = mb_strtolower($match[1], 'utf-8');
    				
    				$name = $match[1];
    				
    				$controller_name = JO_Front::getInstance()->formatControllerName('extensions_' . $key);
    				
	    			if(!class_exists($controller_name, false)) {
						JO_Loader::loadFile($file);
					}
					
	    			if(method_exists($controller_name, 'info')) {
						$data = call_user_func(array($controller_name, 'info')); 
						if(isset($data['name']) && $data['name']) {
							$name = $data['name'];
						}
					}
    				
    				$this->view->extensions[] = array(
    					'key' => $key,
						'install' => $request->getModule() . '/extensions/install/?extension=' . $key,
    					'uninstall' => $request->getModule() . '/extensions/uninstall/?extension=' . $key,
    					'edit' => $request->getModule() . '/extensions/edit/?extension=' . $key,
    					'name' => $name,
    					'sort' => (int)JO_Registry::forceGet($key . '_sort_order'),
    					'status' => ( JO_Registry::forceGet($key . '_status') ? $this->translate('Enabled') : $this->translate('Disabled') ),
    					'installed' => Model_Extensions::isInstaled($key)
    				);
    			}
    		}
    	}	
	}
	
	public function installAction() {
		
		$request = $this->getRequest();
		
		$extensions = array();
		
		$files = glob(dirname(__FILE__) . '/Extensions/*.php');
		if($files) {
    		foreach($files AS $file) { 
    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
    				$extensions[] = mb_strtolower($match[1], 'utf-8');
    			}
    		}
    	}
		
		$extension = $request->getQuery('extension');
		
		if(in_array($extension, $extensions)) {
			$res = Model_Extensions::install($extension);
			if($res) {
				
				$module_name = JO_Front::getInstance()->formatModuleName('model_extensions_' . $extension);
				$file_model = APPLICATION_PATH . '/modules/' . $request->getModule() . '/' . JO_Front::getInstance()->classToFilename($module_name);
				if(file_exists($file_model)) {
					if(!class_exists($module_name, false)) {
						JO_Loader::loadFile($file_model);
					}
					if(method_exists($module_name, 'install')) {
						call_user_func(array($module_name, 'install'));
					}
				} else {
					$module_name = JO_Front::getInstance()->formatControllerName('extensions_' . $extension);
					$file_model = APPLICATION_PATH . '/modules/' . $request->getModule() . '/controllers/' . JO_Front::getInstance()->classToFilename($module_name);
					if(file_exists($file_model)) {
						if(!class_exists($module_name, false)) {
							JO_Loader::loadFile($file_model);
						}
						if(method_exists($module_name, 'install')) {
							call_user_func(array($module_name, 'install'));
						}
					}
				}
				$this->session->set('successfu_edite', true);
			}
		}
		
		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/extensions/');
		
	}
	
	public function uninstallAction() {
		
		$request = $this->getRequest();
		
		$extensions = array();
		
		$files = glob(dirname(__FILE__) . '/Extensions/*.php');
		if($files) {
    		foreach($files AS $file) { 
    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
    				$extensions[] = mb_strtolower($match[1], 'utf-8');
    			}
    		}
    	}
		
		$extension = $request->getQuery('extension');
		
		if(in_array($extension, $extensions)) {
			$res = Model_Extensions::uninstall($extension);
			if($res) {
				
				$module_name = JO_Front::getInstance()->formatModuleName('model_extensions_' . $extension);
				$file_model = APPLICATION_PATH . '/modules/' . $request->getModule() . '/' . JO_Front::getInstance()->classToFilename($module_name);
				if(file_exists($file_model)) {
					if(!class_exists($module_name, false)) {
						JO_Loader::loadFile($file_model);
					}
					if(method_exists($module_name, 'uninstall')) {
						call_user_func(array($module_name, 'uninstall'));
					}
				} else {
					$module_name = JO_Front::getInstance()->formatControllerName('extensions_' . $extension);
					$file_model = APPLICATION_PATH . '/modules/' . $request->getModule() . '/controllers/' . JO_Front::getInstance()->classToFilename($module_name);
					if(file_exists($file_model)) {
						if(!class_exists($module_name, false)) {
							JO_Loader::loadFile($file_model);
						}
						if(method_exists($module_name, 'uninstall')) {
							call_user_func(array($module_name, 'uninstall'));
						}
					}
				}
				
				$this->session->set('successfu_edite', true);
			}
		}
		
		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/extensions/');
		
	}
	
	public function editAction() {
		
		$request = $this->getRequest();
		
		$extensions = array();
		
		$files = glob(dirname(__FILE__) . '/Extensions/*.php');
		if($files) {
    		foreach($files AS $file) { 
    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
    				$extensions[] = mb_strtolower($match[1], 'utf-8');
    			}
    		}
    	}
		
		$extension = $request->getQuery('extension');
		$action = $request->getRequest('call', 'index');
		
		if(in_array($extension, $extensions)) {
			$this->forward('extensions_' . $extension, $action);
		}
		
		$this->forward('error', 'error404');
		
	}

}

?>