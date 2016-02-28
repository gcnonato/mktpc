<?php

class UsergroupsController extends JO_Action {
	
	public static function config() {
		$is_singlesignon = false;
		if(JO_Registry::get('singlesignon_db_users') && JO_Registry::get('singlesignon_db_users') != JO_Db::getDefaultAdapter()->getConfig('dbname')) {
			$is_singlesignon = true;	
		}
		return array(
			'name' => self::translate('Users Groups management'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'users',
			'sort_order' => 22000,
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
        
		$this->view->groups = array();
        $groups = Model_Usergroups::getGroups();
        if($groups) {
            
            foreach($groups AS $group) {
            	$group['description'] = html_entity_decode($group['description'], ENT_QUOTES, 'utf-8');
                $group['nodelete'] = array_key_exists($group['ug_id'], (array)unserialize(JO_Session::get('groups')));
            	
            	$this->view->groups[] = $group;
                
            }
        } 
	}
	
	public function createAction() {
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Usergroups::createUserGroup($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/usergroups/');
    	}
		
		$this->getForm();
	}
	
	public function editeAction() {
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Usergroups::editeUserGroup($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/usergroups/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Usergroups::deleteUserGroup($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$group_id = $request->getQuery('id');
		
		$modelGroup = new Model_Usergroups;
		
		if($group_id) {
			$group_info = $modelGroup->getUserGroup($group_id);
		}
		
		if($request->getPost('name')) {
			$this->view->name = $request->getPost('name');
		} elseif(isset($group_info)) {
			$this->view->name = $group_info['name'];
		}
		
		if($request->getPost('description')) {
			$this->view->description = $request->getPost('description');
		} elseif(isset($group_info)) {
			$this->view->description = $group_info['description'];
		}
		
		if($request->isPost()) {
			$this->view->access = (array)$request->getPost('access');
		} elseif(isset($group_info)) {
			$this->view->access = $group_info['access'];
		} else {
			$this->view->access = array();
		}
		
		$access_modules = JO_Registry::forceGet('temporary_for_permision');
		$this->view->access_modules = array();
		foreach($access_modules AS $group => $models) {
			foreach($models AS $model) {
				if(isset($this->view->access_modules[$group])) {
					$this->view->access_modules[$group]['name'] = $this->view->access_modules[$group]['name'] . ', ' .$model['name'];
				} else {
					$this->view->access_modules[$group] = array(
						'key' => $model['key'],
						'name' => $model['name']
					);
				}
			}
		}
		
		
		
	} 

}

?>