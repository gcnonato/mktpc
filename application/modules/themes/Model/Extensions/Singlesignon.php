<?php

class Model_Extensions_Singlesignon {

	public static function checkUser($referer, $domain, $openId) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'users')
					->where(new JO_Db_Expr("MD5(CONCAT('{$referer}','{$domain}', `username`, `email`)) = '" . $openId . "'"));
					
		return $db->fetchRow($query);
	}
	
	public static function createUser($data) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!$data['username'] || Model_Users::getByUsername($data['username'])) {
			return false;
		}
		
		if(!$data['email'] || Model_Users::getByEmail($data['email'])) {
			return false;
		}
		
		$db->insert(Model_Users::getPrefixDB().'users', array(
			'username' => $data['username'],
			'password' => $data['password'],
			'email' => $data['email'],
			'firstname' => $data['firstname'],
			'lastname' => $data['lastname'],
			'firmname' => $data['firmname'],
			'profile_title' => $data['profile_title'],
			'profile_desc' => $data['profile_desc'],
			'register_datetime' => $data['register_datetime'],
			'status' => 'activate'
		));
		
		$user_id = $db->lastInsertId();
		
		if($user_id) {
			
			$upload_path = BASE_PATH . '/uploads';
			$user_path = '/users/' . JO_Date::getInstance($data['register_datetime'], 'yy/mm/')->toString() . $user_id . '/';
			$upload_path .= $user_path;
			
			if($data['avatar'] && @getimagesize($data['avatar'])) {
				$name = basename($data['avatar']);
				if( copy($data['avatar'], $upload_path . $name) ) {
					$db->update('users', array(
						'avatar' => $user_path . $name
					), array('user_id' => $user_id));
				}
			}
			
			if($data['homeimage'] && @getimagesize($data['homeimage'])) {
				$name = basename($data['homeimage']);
				if( copy($data['homeimage'], $upload_path . $name) ) {
					$db->update('users', array(
						'homeimage' => $user_path . $name
					), array('user_id' => $user_id));
				}
			}
			
			return Model_Users::getUser($user_id);
		}
		
	}
	
}

?>