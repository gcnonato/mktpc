<?php

class Model_Users {

	public static function getPrefixDB() {
		if(JO_Registry::get('singlesignon_db_users')) {
			return JO_Registry::get('singlesignon_db_users') . '.';
		}
		return '';
	}
	
	public static function createUser($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert(self::getPrefixDB().'users', array(
			'username' => (string)$data['username'], 
			'password' => (string)md5($data['password']),
			'active' => 1,
		));
	}
	
	public static function getFBuser($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT * FROM users WHERE fb_id = '. $db->quote($id);
		
		return $db->fetchRow($query);
	}
	
	public static function editeUser($user_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update(self::getPrefixDB().'users', array(
			'username' => (string)$data['username'],
			'password' => (string)($data['password'] ? md5($data['password']) : new JO_Db_Expr('password'))
		), array('user_id = ?' => (int)$user_id));
	}
	
    public static function editPass($user_id, $pass) {
		$db = JO_Db::getDefaultAdapter();
		$db->update(self::getPrefixDB().'users', array(
			'password' => (string)md5(md5($pass))
		), array('user_id = ?' => (int)$user_id));
	}
	
	public static function getUser($user_id) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users')
							->where('user_id = ?', (int)$user_id)
							->limit(1,0);
		return $db->fetchRow($query);
	}
	
    public static function getFeatUser() {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users')
							->where('status = ?', 'activate')
							->where('featured_author = ?', 'true')
							->order('rand()')
							->limit(1,0);
		return $db->fetchRow($query);
	}
	
public static function getFeatAuthors($where = false) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users')
							->where('status = ?', 'activate')
							->where('featured_author = ?', 'true')
							->limit(20);
							

		return $db->fetchAll($query);
	}
	
    public static function getByUsername($username) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users')
							->where('username = ?', $username)
							->limit(1,0);
							
							
							
		return $db->fetchRow($query);
	}
	
    public static function getByEmail($email) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users')
							->where('email = ?', $email)
							->limit(1,0);
							
							
							
		return $db->fetchRow($query);
	}
	
	public static function getUsers() {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users');
		return $db->fetchAll($query);
	}

	
	public function checkLogin($username, $password) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users')
							->where('username = ?', (string)$username)
							->where('password = ?', (string)md5(md5($password)))
							->where('status = ?', 'activate')
							->limit(1,0); 
	    
		return $db->fetchRow($query);
	}
	
	public function getGroups($groups) {
	    	$db = JO_Db::getDefaultAdapter();	
	    	$query_group = $db->select()
	    							->from(self::getPrefixDB().'user_groups')
	    							->where("ug_id IN (?)", new JO_Db_Expr(implode(',', array_keys($groups))));
	    		return  $db->fetchAll($query_group);
	}
	
	public function getFollowers($user_id, $start = 0, $limit = false, $fl = false, $order = false) {
	        $col = $fl ? 'follow_id' : 'user_id'; 
	        $col2 = $fl ? 'user_id' : 'follow_id'; 
			
	    	$db = JO_Db::getDefaultAdapter();	
	    	$query_group = $db->select()
	    							->from('users_followers')
	    							->joinLeft(self::getPrefixDB().'users', 'users.user_id = users_followers.'.$col2, array('username'))
	    							->where("users_followers.".$col." = ?", (int)$user_id)
	    							//->group('users_followers.user_id')
	    							;
	    	if($order)
				$query_group->order($order);
			
	    	if($limit)
				$query_group->limit($limit, $start);
				//echo $query_group;				
	    		return  $db->fetchAll($query_group);
	}
	
    public function countFollowers($user_id, $fl=false) {
	        $col = $fl==true ? 'follow_id' : 'user_id'; 
	        $col2 = $fl!=true ? 'follow_id' : 'user_id'; 
	    	$db = JO_Db::getDefaultAdapter();	
	    	$query_group = $db->select()
	    							->from('users_followers', new JO_Db_Expr('COUNT(user_id)'))
	    							->where("users_followers.".$col." = ?", (int)$user_id);
	    		return  $db->fetchOne($query_group);
	}
	
    public function isFollow($id, $user_id) {
        $db = JO_Db::getDefaultAdapter();
		$query_group = $db->select()
	    							->from('users_followers', new JO_Db_Expr('COUNT(user_id)'))
	    							->where("user_id = ?", (int)$user_id)
	    							->where("follow_id = ?", (int)$id);
	    		return  $db->fetchOne($query_group) == 0 ? false : true;
	}
	
	public function addFollow($id, $user_id) {
	    $db = JO_Db::getDefaultAdapter();
	    $db->insert('users_followers', array(
	    'user_id' => (int)$user_id,
	    'follow_id' => (int)$id
	      ));
	    
	}
	
	public function deleteFollow($id, $user_id) {
		 $db = JO_Db::getDefaultAdapter();
		 
	
		 $db->delete('users_followers', array('user_id = ?'=>(int)$user_id, 'follow_id = ?'=>(int)$id));
	}
	
	public function followUser($id, $user_id) {
		if(Model_Users::isFollow($id, $user_id)) {
			Model_Users::deleteFollow($id, $user_id);
		}
		else {
			Model_Users::addFollow($id, $user_id);
		}
		
		return true;
	}
	
	public function topUsers($start = 0, $limit = 9, $order = 'position asc', $list_type = 'top') {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db->select()
					->from(self::getPrefixDB().'users', new JO_Db_Expr('*, 
																		(SELECT COUNT(`user_id`)
																		FROM `items` 
																		WHERE `items`.`user_id` = `users`.`user_id` 
																			AND `items`.`status` = \'active\' LIMIT 1) AS aitems,
																		(SELECT COUNT(follow_id) 
																		FROM `users_followers` 
																		WHERE `follow_id` = `users`.`user_id` LIMIT 1) AS followers, 
																		(SELECT COUNT(user_id) 
																		FROM `users_followers` 
																		WHERE `user_id` = `users`.`user_id` LIMIT 1 ) AS following'. ( $list_type == 'top' ? ',  
																		(SELECT p.`position`
																		FROM (SELECT (@rownum := @rownum + 1) as position, u.`user_id` 
																			FROM `users` u, (SELECT @rownum := 0) r
																			WHERE u.`sales` > 0 AND u.`status` = \'activate\' ORDER BY u.`sales` DESC ) p
																		WHERE p.`user_id` = `users`.`user_id`) AS position' : '')))
					->where('status = ?', 'activate')
					->where('sales > 0')
					->order($order)
					->limit($limit, $start);
		//echo $query;exit;
		return $db->fetchAll($query);
	}
	
    public function CountTopUsers() {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(self::getPrefixDB().'users', new JO_Db_expr('COUNT(`user_id`)'))
							->where('status = ?', 'activate')
							->where('sales > 0')
							->order('sales DESC');
		return $db->fetchOne($query);
		
	}
	
 public function countUsers() {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(self::getPrefixDB().'users', new JO_Db_Expr('COUNT(user_id)'))
					->where('status = ?', 'activate');
		
		return $db->fetchOne($query);	
	}
	
	public function editPassword($user_id, $data) {
	    $user = Model_Users::getUser($user_id);
	    $error = array();
	    if(md5(md5($data['password']))!= $user['password']) {
	        $error['epassword'] = $this->translate('Your old password does not match.');
	    }
	    
	    if(strlen($data['new_password'])<4) {
	        $error['enew_password'] = $this->translate('Your new password must be at least 4 symbols');
	    }
	    
	    if($data['new_password'] != $data['new_password_confirm']) {
	        $error['enew_password_confirm'] = $this->translate('Your new passwords does not match.');
	    }
	    
	    if(count($error) > 0) return $error;
	    
	    $db = JO_Db::getDefaultAdapter();
	    $db->update(self::getPrefixDB().'users', array(
			'password' => md5(md5($data['new_password']))
		), array('user_id = ?' => (int)$user_id));
		
		return true;
	}
	
	public function editFeatureItem($user_id, $data) {
	     $db = JO_Db::getDefaultAdapter();
		$db->update(self::getPrefixDB().'users', array(
			'featured_item_id' => $data['featured_item_id']
		), array('user_id = ?' => (int)$user_id));
		JO_Session::set('featured_item_id', $data['featured_item_id']);
		return true;
	}
	
	public function editExclusive($user_id, $data) {

	    $db = JO_Db::getDefaultAdapter();
		$db->update(self::getPrefixDB().'users', array(
			'exclusive_author' => $data
		), array('user_id = ?' => (int)$user_id));
		
		return true;
	}
	
    public function editLicense($user_id, $data) {

	    $db = JO_Db::getDefaultAdapter();
		$license = serialize($data);
		
		$db->update(self::getPrefixDB().'users', 
		array('license' => $license), 
		array('user_id = ?' => (int)$user_id));
		
		JO_Session::set('license', $license);
		
		return true;
	}
	
    public function editSocial($user_id, $data) {

	    $db = JO_Db::getDefaultAdapter();
		$db->update(self::getPrefixDB().'users', array(
			'social' => serialize($data)
		), array('user_id = ?' => (int)$user_id));
		JO_Session::set('social', serialize($data));
		return true;
	}
	
	public function editPersonal($user_id, $data) {
        $db = JO_Db::getDefaultAdapter();
		$db->update(self::getPrefixDB().'users', array(
			'firstname' => $data['firstname'],
			'lastname' => $data['lastname'],
			'email' => $data['email'],
			'firmname' => $data['firmname'],
			'profile_title' => $data['profile_title'],
			'profile_desc' => $data['profile_desc'],
			'live_city' => $data['live_city'],
			'country_id' => $data['country_id'],
			'freelance' => $data['freelance'],
			'item_note' => $data['item_note'],
			'daily' => $data['daily'],
			'fb_id' => $data['fb_id']
		), array('user_id = ?' => (int)$user_id));
		
		
		
		
		JO_Session::set('firstname', $data['firstname']);
		JO_Session::set('lastname', $data['lastname']);
		JO_Session::set('email', $data['email']);
		JO_Session::set('firmname', $data['firmname']);
		JO_Session::set('profile_title', $data['profile_title']);
		JO_Session::set('profile_desc', $data['profile_desc']);
		JO_Session::set('live_city', $data['live_city']);
		JO_Session::set('country_id', $data['country_id']);
		JO_Session::set('freelance', $data['freelance']);
		
		return true;
	    
	}
	
    public function updateQuiz($user_id, $true) {

	    $db = JO_Db::getDefaultAdapter();
		$db->update(self::getPrefixDB().'users', array(
			'quiz' => $true
		), array('user_id = ?' => (int)$user_id));
		return true;
	}
	
	public function ValidMail($email) {
	    return (preg_match ( '/^[-!#$%&\'*+\\.\/0-9=?A-Z^_`{|}~]+' . '@' . '([-0-9A-Z]+\.)+' . '([0-9A-Z]){2,4}$/i', trim ( $email ) ));
	}
	
	public function isExistEmail($email, $usermail=FALSE) {
	    if($email==$usermail)
	        return false;
	        
	      $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(self::getPrefixDB().'users', new JO_Db_Expr('COUNT(user_id)'))
					->where('email = ?', $email);
		
		return $db->fetchOne($query)>0 ? true : false;	
	}
	
    public function isExistEmailUsername($email, $username) {
	
	        
	      $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(self::getPrefixDB().'users', new JO_Db_Expr('COUNT(user_id)'))
					->where('email = ?', $email)
					->where('username =?', $username);
		
		return $db->fetchOne($query)>0 ? true : false;	
	}
	
    public function isExistUsername($username) {

	        
	      $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(self::getPrefixDB().'users', new JO_Db_Expr('COUNT(user_id)'))
					->where('username = ?', $username);
		
		return $db->fetchOne($query)>0 ? true : false;	
	}
	    
    public function editAvatar($user_id, $data) {

	    $db = JO_Db::getDefaultAdapter();
	    
		$db->update(self::getPrefixDB().'users', array(
			'avatar' => $data
		), array('user_id = ?' => (int)$user_id));
		
		JO_Session::set('avatar', $data);
          if(JO_Registry::get('singlesignon_enable_login') == '1' and JO_Registry::get('singlesignon_status') == '1') {
		    $query = $db->select()
		    ->from('system')
		    ->where('`group` = ?', 'single_sign_on');
	
    		foreach($db->fetchAll($query) as $d) {  
    		        $dat = unserialize($d['value']);
    		        if(is_writable($dat['home_dir']) and $dat['home_dir']) {
    		            $dir = explode('/', $data);
    		            unset($dir[count($dir)-1]);
    		            $dir = implode('/', $dir);
    		            @mkdir($dat['home_dir'].$dir, 0777, true);
                        copy(realpath(BASE_PATH . '/uploads').$data, $dat['home_dir'].$data);
    		        }
    		}
		}
		return true;
	}
	
 public function editHomeimage($user_id, $data) {

	    $db = JO_Db::getDefaultAdapter();
	    
		$db->update(self::getPrefixDB().'users', array(
			'homeimage' => $data
		), array('user_id = ?' => (int)$user_id));
		
		JO_Session::set('homeimage', $data);
       if(JO_Registry::get('singlesignon_enable_login') == '1' and JO_Registry::get('singlesignon_status') == '1') {
		    $query = $db->select()
		    ->from('system')
		    ->where('`group` = ?', 'single_sign_on');
	
    		foreach($db->fetchAll($query) as $d) {  
    		        $dat = unserialize($d['value']);
    		        if(is_writable($dat['home_dir']) and $dat['home_dir']) {
    		            $dir = explode('/', $data);
    		            unset($dir[count($dir)-1]);
    		            $dir = implode('/', $dir);
    		            @mkdir($dat['home_dir'].$dir, 0777, true);
                        copy(realpath(BASE_PATH . '/uploads').$data, $dat['home_dir'].$data);
    		        }
    		}
		}
		return true;
	}
	
	public function sendEmail($user_id, $email, $user2_id, $message) {
	    
	      $db = JO_Db::getDefaultAdapter();
	      $db->insert('users_emails', array (
	      'from_id' => $user_id,
	      'from_email' => $email,
	      'to_id' => $user2_id,
	      'message' => $message,
	      'datetime' => new JO_Db_Expr('NOW()')
	      ));
	}
	
	public function getTotalReferals($user_id, $referal_id) {
		 $db = JO_Db::getDefaultAdapter();
		 
		 $query = $db->select()
		    ->from(Model_Users::getPrefixDB().'users_referals_count', new JO_Db_Expr('COUNT(id) as sum'))
		    ->where('user_id = ?', $user_id)
		    ->where('referal_id = ?', $referal_id)
			->where('order_type NOT IN (\'register\', \'gast\')')
		    ->group('referal_id')
		    ->limit(1,0);
		
		
		return $db->fetchOne($query);
	}
	
    public function getTopAuthors($start=0, $limit=0, $where=false) {

        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders', new JO_Db_Expr('* , COUNT( `item_id` ) AS `sales` '))
					->joinLeft('items', 'items.id = orders.item_id')
					->where('items.status = ?', 'active')
					->where('orders.type = ?', 'buy')
					->where('orders.paid = ?', 'true')
					->limit($limit, $start)
					->group('orders.owner_id');
		if($where!=false) {
		    $query->where($where);
		}

		$return = array();
		
        
		
		foreach($db->fetchAll($query) as $d) {  
		    $return[$d['owner_id']] = Model_Users::getUser($d['owner_id']);
		}
		return $return; 
	}
	
	public function register($data) {

	     $db = JO_Db::getDefaultAdapter();
	     $db->insert(self::getPrefixDB().'users', array(
	     'username'	=> $data['username'],
	     'password'	=> $data['password'],
	     'email'	=> $data['email'],
	     'firstname'	=> $data['firstname'],
	     'lastname'	=> $data['lastname'],
	     'register_datetime'	=> new JO_Db_Expr('NOW()'),
	     'activate_key'	=> $data['activate_key'],
	     'referal_id'	=> $data['referal_id'],
	     'fb_id' => $data['fb_id']
	     ));
	    
		 
		 
	     $user_id = $db->lastInsertId();
		 
	     if(isset($data['referal_id']) && $data['referal_id'] != 0) {
	     	$db->insert(self::getPrefixDB() .'users_referals_count', array(
				'user_id' => $user_id,
				'referal_id' => $data['referal_id'],
				'datetime' => new JO_Db_Expr('NOW()'),
				'order_type' => 'register'
			));
	     }
	     
	}
	
	public function addref($user_id) {
	     $db = JO_Db::getDefaultAdapter();
	     $db->update(self::getPrefixDB().'users', array('referals'=>new JO_Db_Expr('referals+1')), array('user_id =?'=>$user_id));
	}
	
	public function checkActivation($username, $key) {
	     $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(self::getPrefixDB().'users', new JO_Db_Expr('COUNT(user_id)'))
					->where('username = ?', $username)
					->where('activate_key =?', $key);
		
		return $db->fetchOne($query)>0 ? true : false;	
	}
	
	public function Activate($username) {
	     $db = JO_Db::getDefaultAdapter();
	     $db->update(self::getPrefixDB().'users', array('activate_key'=> '', 'status'=>'activate'), array('username=?'=>$username));
	}
	
	public static function getAll($start, $limit, $whereQuery='', $order = '') {
		$db = JO_Db::getDefaultAdapter();
		
		if($whereQuery != '') {
			$whereQuery = " WHERE ".$whereQuery;
		}
		if($order != '') {
			$order = " ORDER BY ".$order;
		}
		
		return $db->query("
			SELECT *,
			( SELECT COUNT(follow_id) FROM `users_followers` WHERE  `user_id` = `u`.`user_id` ) AS followers
			FROM ".Model_Users::getPrefixDB()."`users` u
			$whereQuery $order
			LIMIT $start, $limit
		")->fetchAll();
	}

	public static function CountUsers2($whereQuery='') {
		$db = JO_Db::getDefaultAdapter();
		
		if($whereQuery != '') {
			$whereQuery = " WHERE ".$whereQuery;
		}
		
		return $db->query("
			SELECT COUNT(user_id)
			FROM ".Model_Users::getPrefixDB()."`users`
			$whereQuery
		")->fetchColumn();
	}
	
	public static function getDailySummary() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT COUNT(o.id) AS cnt, o.owner_id, 
					u.username, u.email, 
					(SELECT SUM(receive) 
					FROM orders 
					WHERE owner_id = u.user_id 
						AND paid_datetime BETWEEN DATE_FORMAT(SUBDATE(NOW(), INTERVAL 1 DAY), "%Y-%m-%d 22-00-00") 
							AND DATE_FORMAT(NOW(), "%Y-%m-%d 22-00-00")
					) AS daily_sum, DATE_FORMAT(SUBDATE(NOW(), INTERVAL 1 DAY), "%Y-%m-%d 22-00-00") as from_date,
					DATE_FORMAT(NOW(), "%Y-%m-%d 22-00-00") as to_date
				FROM users u
				LEFT JOIN orders o ON o.owner_id = u.user_id
				WHERE u.daily = \'true\' AND paid_datetime BETWEEN DATE_FORMAT(SUBDATE(NOW(), INTERVAL 1 DAY), "%Y-%m-%d 22-00-00") 
					AND DATE_FORMAT(NOW(), "%Y-%m-%d 22-00-00")
				GROUP BY u.username';
		
		return $db->fetchAll($query);
	}
}

?>