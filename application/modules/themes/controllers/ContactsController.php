<?php

class ContactsController extends JO_Action {

	private $error = array();
	
	public function indexAction() {
		
		$this->getLayout()->meta_title = $this->translate('Contacts');
    	$this->getLayout()->meta_description = $this->translate('Contacts');
		$request = $this->getRequest();
		
		if(JO_Session::issetKey('msg_success')) {
			$this->view->is_send = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::issetKey('msg_error')) {
			$this->view->error = JO_Session::get('msg_error');
			$this->view->user = JO_Session::get('data');
			JO_Session::clear('msg_error');
			JO_Session::clear('data');
		}
		
		$this->view->categories = array();
		$categories = Model_Contactscategories::getCategories();	
		
		if($categories) {
			foreach($categories AS $category) {
				$this->view->categories[$category['id']] = array(
					'id' => $category['id'],
					'name' => $category['name'],
					'text' => html_entity_decode($category['text'], ENT_QUOTES, 'utf-8')
				);
			}
		}

		if(empty($this->view->user['username'])) {
			$this->view->user['username'] = JO_Session::get('username');
		}
		
		if(empty($this->view->user['email'])) {
			$this->view->user['email'] = JO_Session::get('email');
		}
		
		$this->view->contact_link = WM_Router::create($request->getBaseUrl() .'?controller=contacts&action=send_mail');
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function send_mailAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if($request->isPost()) {			
			if($this->validateForm()) {
					
				$issue_id = $request->getPost('issue_id');
				if($issue_id > 0) {
					$category_issue = Model_Contactscategories::getCategory($issue_id);
					$category = $category_issue['name'];
				} else {
					$category = $this->translate('Not selected');
				}
				
				$request->setParams('issue', $category);
				$res = Model_Contacts::addContact($request->getParams());
				JO_Session::set('msg_success', $this->translate('The mail is sent successfully'));
			} else {
				JO_Session::set('msg_error', $this->error);
				JO_Session::set('data', $request->getParams());
			}
		} 
		$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=contacts'));
	}
	
	private function validateForm() {
		
		$request = $this->getRequest();
		
		$user_strlen = mb_strlen($request->getPost('username'));
		if($user_strlen < 3 || $user_strlen > 64) {
			$this->error['uname'] = $this->view->translate('Username must be between 3 and 64 characters!');
		}
		
		if(!preg_match('/^[A-Z0-9._%-+]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,6}$/i', $request->getPost('email'))) {
			$this->error['uemail'] = $this->view->translate('E-Mail Address does not appear to be valid!');
		}
		
		if($this->error) {
			return false;
		} else {
			return true;
		}
		
	}
	
}

?>