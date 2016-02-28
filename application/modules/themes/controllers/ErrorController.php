<?php

class ErrorController extends JO_Action {
	
	public function error404Action() { 
		
		$this->getResponse()->addHeader("HTTP/1.0 404 Not Found");
		$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function noLicenseAction() { 
		
		$this->view->errors = JO_Registry::forceGet('LicenseError');
		
		$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
}

?>