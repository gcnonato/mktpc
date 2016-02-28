<?php

class WM_Users {

	public static function getPrefixDB() {
		if(JO_Registry::get('singlesignon_db_users')) {
			return JO_Registry::get('singlesignon_db_users') . '.';
		}
		return '';
	}

	public static function initSession($user_id) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users')
							->where('user_id = ?', (int)$user_id)
							->limit(1,0);
		$user_data = $db->fetchRow($query);
		
		if($user_data && $user_data['status'] == 'activate') {
			$groups = unserialize($user_data['groups']);
	    	if(is_array($groups) && count($groups) > 0) {
	    		$query_group = $db->select()
	    							->from(self::getPrefixDB().'user_groups')
	    							->where("ug_id IN (?)", new JO_Db_Expr(implode(',', array_keys($groups))));
	    		$fetch_all = $db->fetchAll($query_group);
	    		$user_data['access'] = array();
	    		if($fetch_all) {
	    			foreach($fetch_all AS $row) {
	    				$modules = unserialize($row['rights']);
	    				if(is_array($modules)) {
	    					foreach($modules AS $module => $ison) {
	    						$user_data['access'][$module] = $module;
	    					}
	    				}
	    			}
	    		}
	    	}
	    	
	    	if(isset($user_data['access']) && count($user_data['access'])) {
	    	    	$user_data['is_admin'] = true;
	    	}
	    	
	    	$db->update(self::getPrefixDB().'users', array(
	    		'last_login_datetime' => new JO_Db_Expr('NOW()'),
	    		'ip_address' => JO_Request::getInstance()->getClientIp()
	    	), array('user_id = ?' => (int)$user_id));
	    	
	    	JO_Session::set($user_data);
	    	
		}

		return $user_data;
		
	}
	
}

?>