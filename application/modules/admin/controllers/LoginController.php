<?php 

class LoginController extends JO_Action {

    public function indexAction() {
    	$this->noLayout(true);
    	$request = $this->getRequest();
    	
    	if($request->getPost('submit')) {
    		$users = new Model_Users;
    		$result = $users->checkLogin($request->getPost('username'),$request->getPost('password'));
    		if(!$result) {
    			$this->view->error = $this->translate('Please enter the correct username and password.');
    		} else {
    			if($result['status'] == 'activate') {
	    			if(isset($result['access']) && count($result['access'])) {
			    	    	$result['is_admin'] = true;
			    	}
    				JO_Session::set($result);
    				header('Location: ' . $request->getServer('HTTP_REFERER'));
    				exit;
    			} else {
    				$this->view->error = $this->translate('This profile is not active.');
    			}
    		}
    	}
    	
    	$this->view->base_url = $request->getBaseUrl();
    	
    }
    
    public function logoutAction() {
    	$this->setInvokeArg('noViewRenderer', true);
    	JO_Session::clear();
    	$this->redirect(JO_Request::getInstance()->getBaseUrl());
    }

}
