<?php 

class IndexController extends JO_Action {
	
	public function init() {
		set_time_limit(0);
		$this->noLayout(true);
	}
	
    public function indexAction() {
    	$request = $this->getRequest();
		
		$this->view->msg_error = array();
		$this->view->msg_success = array();
		
		//begin updater
		$sys_config = APPLICATION_PATH . '/config/config_db.ini';
		
		if(!file_exists($sys_config)) {
			$this->view->msg_error['old_sys'] = 'System Db config file not found: <strong>' . $sys_config . '</strong>';
		} elseif(!is_writable($sys_config)) {
			$this->view->msg_error['old_sys'] = '<strong>' . $sys_config . '</strong> must be writable!';
		}
		
		$upload_folder = BASE_PATH . '/uploads/';
		if(!is_writable($upload_folder)) {
			$this->view->msg_error['upload'] = '<strong>' . $upload_folder . '</strong> must be writable!';
		}
		$upload_folder = BASE_PATH . '/cache/';
		if(!is_writable($upload_folder)) {
			$this->view->msg_error['cache'] = '<strong>' . $upload_folder . '</strong> must be writable!';
		}
		
		if(!$this->view->msg_error && $request->getPost('install') == 'yes') {
			
			$db_params = $request->getPost('params');
			$db_params['charset'] = 'utf8';
			
			foreach($db_params AS $key => $value) {
				if(!$value && in_array($key, array('password','dbname'))) {
					$db_params[$key] = ' ';
				}
			}
			
			$error = false;
			
			try {
				$db = JO_Db::factory("MYSQLi", $db_params );
				$db->query("ALTER DATABASE `" . $request->getPost('params[dbname]') . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
				$db->query("SET NAMES 'utf8'");
			} catch (JO_Db_Exception $e) {
				$error = $e->getMessage();
			}
			
			if($db->isConnected()) {
				
				if(trim($request->getPost('username')) == '') {
		            $this->view->msg_error['username'] = 'You must type your username';
		        }
		        elseif(!preg_match('/^[a-zA-Z0-9_]+$/i', $request->getPost('username'))) {
		            $this->view->msg_error['username'] = 'The username you have entered is not valid';
		        }
		        if(trim($request->getPost('password')) == '') {
		            $this->view->msg_error['password'] = 'You must type your password';
	        	}
				if(trim($request->getPost('admin_mail')) == '') {
		            $this->view->msg_error['admin_mail'] = 'You must type your email';
		        }
		        elseif(!self::ValidMail($request->getPost('admin_mail'))) {
		            $this->view->msg_error['admin_mail'] = 'You must type valid email';
		        }
				if(trim($request->getPost('report_mail')) == '') {
		            $this->view->msg_error['report_mail'] = 'You must type your email';
		        }
		        elseif(!self::ValidMail($request->getPost('report_mail'))) {
		            $this->view->msg_error['report_mail'] = 'You must type valid email';
		        }
				
	        	if(count($this->view->msg_error) == 0) {
	        		if($request->getPost('demo') == 'yes') {
	        			$result = Model_Install::installWithDemo($db);
	        		} else {
	        			$result = Model_Install::installWithoutDemo($db);
	        		}
	        		
	        		if($result) {
	        			JO_Action::getInstance()->redirect($request->getBaseUrl() . '?module=install&action=success');
	        		} else {
	        			$this->view->msg_error['install_error'] = 'An error occurred during installation. Try again to install and the installation fails, contact <a target="_blank" title="marketplace script" href="http://cloneforest.com"> Marketplace script </a>';
	        		}
	        	}
	        	
			} else {
				$this->view->msg_error['db_connect'] = $error;
			}
		}
    }
	
	public function ValidMail($email) {
	    return (preg_match ( '/^[-!#$%&\'*+\\.\/0-9=?A-Z^_`{|}~]+' . '@' . '([-0-9A-Z]+\.)+' . '([0-9A-Z]){2,4}$/i', trim ( $email ) ));
	}
    
    public function successAction() {
    	$this->setViewChange('index');
    	
    	$fordel = array(
    		APPLICATION_PATH . '/modules/install/'
    	);
    	
    	$this->view->msg_success = 'Your installation is ready for use. Now please delete the following folders: <b>' . implode('</b>; <b>', $fordel) . '<b>';
    }
    

}
