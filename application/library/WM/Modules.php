<?php

class WM_Modules {

	public static function getList($ignore = array()) {
		
		$modules_path = rtrim(JO_Front::getInstance()->getModuleDirectory(), '/');
    	$list = glob($modules_path . '/*');
    	$modules = array();
    	
    	if($list) {
    		foreach($list AS $dir) {
    			if(basename($dir) != 'admin') {
    				if(in_array(basename($dir), $ignore)) { continue; }
    				$modules[] = basename($dir);
    			}
    		}
    	} 
    	
    	return $modules;
	}

	public static function getConfig() {
		$modules_path = rtrim(JO_Front::getInstance()->getModuleDirectory(), '/');
    	$list = glob($modules_path . '/*');
    	$modules = array();
    	
    	if($list) {
    		foreach($list AS $dir) {
    			if(!in_array(basename($dir), array('admin','update','install'))) {
    				if(file_exists($dir . '/config.ini')) {
    					$config = new JO_Config_Ini($dir . '/config.ini');
    					$modules[basename($dir)] = $config->toArray();
    				}
    			}
    		}
    	} 
    	return $modules;
	}
	
}

?>