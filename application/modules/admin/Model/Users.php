<?php

class Model_Users {

	public static function getPrefixDB() {
		if(JO_Registry::get('singlesignon_db_users')) {
			return JO_Registry::get('singlesignon_db_users') . '.';
		}
		return '';
	}

	public function __construct() {}
	
	public static function createUser($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array(
			'commission_percent' => (int)$data['commission_percent'],
			'featured_author' => $data['featured_author'],
			'status' => $data['status']
		);
		
		if(trim($data['password'])) {
			$insert['password'] = md5(md5($data['password']));
		}
		
		$insert['author_status'] = trim($data['author_status']);
		$insert['author_status_description'] = trim($data['author_status_description']);
		
		if(isset($data['badges']) && is_array($data['badges'])) {
			$insert['badges'] = implode(',', $data['badges']);
		} else {
			$insert['badges'] = '';
		}
		
		if(isset($data['groups']) && is_array($data['groups'])) {
			$groups = $data['groups'];
		} else {
			$groups = array();
		}
		
		$insert['groups'] = serialize($groups);
		
		$db->insert(Model_Users::getPrefixDB().'users', $insert);
		
		return $db->lastInsertId();
		
	}
	
	public static function editeUser($user_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array(
			'commission_percent' => (int)$data['commission_percent'],
			'featured_author' => $data['featured_author'],
			'status' => $data['status']
		);
		
		if(trim($data['password'])) {
			$insert['password'] = md5(md5($data['password']));
		}
		
		$insert['author_status'] = trim($data['author_status']);
		$insert['author_status_description'] = trim($data['author_status_description']);
		
		if(isset($data['badges']) && is_array($data['badges'])) {
			$insert['badges'] = implode(',', $data['badges']);
		} else {
			$insert['badges'] = '';
		}
		
		if(isset($data['groups']) && is_array($data['groups'])) {
			$groups = $data['groups'];
		} else {
			$groups = array();
		}
		
		$insert['groups'] = serialize($groups);
//		var_dump($insert); exit;
		$db->update(Model_Users::getPrefixDB().'users', $insert, array('user_id = ?' => (int)$user_id));
		
		return $user_id;
	}
	
	public static function deleteUser($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete(Model_Users::getPrefixDB().'users', array('user_id = ?' => (int)$user_id));
	}
	
	public static function getUser($user_id) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(Model_Users::getPrefixDB().'users')
							->where('user_id = ?', (int)$user_id)
							->limit(1,0);
		return $db->fetchRow($query);
	}
	
	public static function getUsers($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$qqq = new JO_Db_Expr("
			COALESCE((SELECT SUM(price) 
			FROM orders 
			WHERE owner_id = u.user_id AND type = 'buy' AND paid = 'true' 
			LIMIT 1), 0) AS sum_price, 
			COALESCE((SELECT SUM(receive) 
			FROM orders 
			WHERE owner_id = u.user_id AND type='buy' AND paid = 'true' 
			LIMIT 1), 0) AS sum_receive, 
			COALESCE((SELECT SUM(referal_sum) 
			FROM users_referals_count 
			WHERE referal_id = u.user_id AND order_type='sale' 
			LIMIT 1), 0) AS sum_referals,
			COALESCE((SELECT COUNT(*) 
			FROM users_referals_count
			WHERE referal_id = u.user_id AND order_type='register'), 0) AS referal_cnt
		");
		
		$query = $db
					->select()
					->from(array('u' => Model_Users::getPrefixDB().'users'), array('*', $qqq));
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'desc') {
			$sort = ' DESC';
		} else {
			$sort = ' ASC';
		}
		
		$allow_sort = array(
			'u.user_id',
			'u.username',
			'u.total',
			'u.sales',
			'u.sold',
			'u.items',
			'u.referals',
			'u.referal_money',
			'u.featured_author',
			'web_profit2'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('username' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('u.user_id = ?', (int)$data['filter_user_id']);
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_total']) && $data['filter_total']) {
			$data['filter_total'] = html_entity_decode($data['filter_total'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_total'], '<>') === 0) {
				$query->where('u.total != ?', (float)substr($data['filter_total'], 2));
			} elseif(strpos($data['filter_total'], '>') === 0) {
				$query->where('u.total > ?', (float)substr($data['filter_total'], 1));
			} elseif(strpos($data['filter_total'], '<') === 0) {
				$query->where('u.total < ?', (float)substr($data['filter_total'], 1));
			} else {
				$query->where('u.total = ?', (float)$data['filter_total']);
			}
		}
		
		if(isset($data['filter_sales']) && $data['filter_sales']) {
			$data['filter_sales'] = html_entity_decode($data['filter_sales'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_sales'], '<>') === 0) {
				$query->where('u.sales != ?', (float)substr($data['filter_sales'], 2));
			} elseif(strpos($data['filter_sales'], '>') === 0) {
				$query->where('u.sales > ?', (float)substr($data['filter_sales'], 1));
			} elseif(strpos($data['filter_sales'], '<') === 0) {
				$query->where('u.sales < ?', (float)substr($data['filter_sales'], 1));
			} else {
				$query->where('u.sales = ?', (float)$data['filter_sales']);
			}
		}
		
		if(isset($data['filter_sold']) && $data['filter_sold']) {
			$data['filter_sold'] = html_entity_decode($data['filter_sold'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_sold'], '<>') === 0) {
				$query->where('u.sold != ?', (float)substr($data['filter_sold'], 2));
			} elseif(strpos($data['filter_sold'], '>') === 0) {
				$query->where('u.sold > ?', (float)substr($data['filter_sold'], 1));
			} elseif(strpos($data['filter_sold'], '<') === 0) {
				$query->where('u.sold < ?', (float)substr($data['filter_sold'], 1));
			} else {
				$query->where('u.sold = ?', (float)$data['filter_sold']);
			}
		}
		
		if(isset($data['filter_web_profit2']) && $data['filter_web_profit2']) {
			$data['filter_web_profit2'] = html_entity_decode($data['filter_web_profit2'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_web_profit2'], '<>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) != ?', (float)substr($data['filter_web_profit2'], 2));
			} elseif(strpos($data['filter_web_profit2'], '>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) > ?', (float)substr($data['filter_web_profit2'], 1));
			} elseif(strpos($data['filter_web_profit2'], '<') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) < ?', (float)substr($data['filter_web_profit2'], 1));
			} else {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) = ?', (float)$data['filter_web_profit2']);
			}
		}
		
		if(isset($data['filter_items']) && $data['filter_items']) {
			$data['filter_items'] = html_entity_decode($data['filter_items'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_items'], '<>') === 0) {
				$query->where('u.items != ?', (float)substr($data['filter_items'], 2));
			} elseif(strpos($data['filter_items'], '>') === 0) {
				$query->where('u.items > ?', (float)substr($data['filter_items'], 1));
			} elseif(strpos($data['filter_items'], '<') === 0) {
				$query->where('u.items < ?', (float)substr($data['filter_items'], 1));
			} else {
				$query->where('u.items = ?', (float)$data['filter_items']);
			}
		}
		
		if(isset($data['filter_referals']) && $data['filter_referals']) {
			$data['filter_referals'] = html_entity_decode($data['filter_referals'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_referals'], '<>') === 0) {
				$query->where('u.referals != ?', (float)substr($data['filter_referals'], 2));
			} elseif(strpos($data['filter_referals'], '>') === 0) {
				$query->where('u.referals > ?', (float)substr($data['filter_referals'], 1));
			} elseif(strpos($data['filter_referals'], '<') === 0) {
				$query->where('u.referals < ?', (float)substr($data['filter_referals'], 1));
			} else {
				$query->where('u.referals = ?', (float)$data['filter_referals']);
			}
		}
		
		if(isset($data['filter_referal_money']) && $data['filter_referal_money']) {
			$data['filter_referal_money'] = html_entity_decode($data['filter_referal_money'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_referal_money'], '<>') === 0) {
				$query->where('u.referal_money != ?', (float)substr($data['filter_referal_money'], 2));
			} elseif(strpos($data['filter_referal_money'], '>') === 0) {
				$query->where('u.referal_money > ?', (float)substr($data['filter_referal_money'], 1));
			} elseif(strpos($data['filter_referal_money'], '<') === 0) {
				$query->where('u.referal_money < ?', (float)substr($data['filter_referal_money'], 1));
			} else {
				$query->where('u.referal_money = ?', (float)$data['filter_referal_money']);
			}
		}
		
		if(isset($data['filter_featured_author']) && in_array($data['filter_featured_author'], array('true','false'))) {
			$query->where('u.featured_author = ?', $data['filter_featured_author']);
		}
	//	echo $query;exit;
		return $db->fetchAll($query);
	}
	
	public static function getTotalUsers($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$qqq = new JO_Db_Expr("
			COUNT(user_id),
			(SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) AS web_profit,
			(SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) AS referral_sum,
			( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) ) AS web_profit2
		");
		
		$query = $db
					->select()
					->from(array('u' => Model_Users::getPrefixDB().'users'), $qqq);
		
		////////////filter
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('u.user_id = ?', (int)$data['filter_user_id']);
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_total']) && $data['filter_total']) {
			$data['filter_total'] = html_entity_decode($data['filter_total'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_total'], '<>') === 0) {
				$query->where('u.total != ?', (float)substr($data['filter_total'], 2));
			} elseif(strpos($data['filter_total'], '>') === 0) {
				$query->where('u.total > ?', (float)substr($data['filter_total'], 1));
			} elseif(strpos($data['filter_total'], '<') === 0) {
				$query->where('u.total < ?', (float)substr($data['filter_total'], 1));
			} else {
				$query->where('u.total = ?', (float)$data['filter_total']);
			}
		}
		
		if(isset($data['filter_sales']) && $data['filter_sales']) {
			$data['filter_sales'] = html_entity_decode($data['filter_sales'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_sales'], '<>') === 0) {
				$query->where('u.sales != ?', (float)substr($data['filter_sales'], 2));
			} elseif(strpos($data['filter_sales'], '>') === 0) {
				$query->where('u.sales > ?', (float)substr($data['filter_sales'], 1));
			} elseif(strpos($data['filter_sales'], '<') === 0) {
				$query->where('u.sales < ?', (float)substr($data['filter_sales'], 1));
			} else {
				$query->where('u.sales = ?', (float)$data['filter_sales']);
			}
		}
		
		if(isset($data['filter_sold']) && $data['filter_sold']) {
			$data['filter_sold'] = html_entity_decode($data['filter_sold'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_sold'], '<>') === 0) {
				$query->where('u.sold != ?', (float)substr($data['filter_sold'], 2));
			} elseif(strpos($data['filter_sold'], '>') === 0) {
				$query->where('u.sold > ?', (float)substr($data['filter_sold'], 1));
			} elseif(strpos($data['filter_sold'], '<') === 0) {
				$query->where('u.sold < ?', (float)substr($data['filter_sold'], 1));
			} else {
				$query->where('u.sold = ?', (float)$data['filter_sold']);
			}
		}
		
		if(isset($data['filter_web_profit2']) && $data['filter_web_profit2']) {
			$data['filter_web_profit2'] = html_entity_decode($data['filter_web_profit2'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_web_profit2'], '<>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) != ?', (float)substr($data['filter_web_profit2'], 2));
			} elseif(strpos($data['filter_web_profit2'], '>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) > ?', (float)substr($data['filter_web_profit2'], 1));
			} elseif(strpos($data['filter_web_profit2'], '<') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) < ?', (float)substr($data['filter_web_profit2'], 1));
			} else {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) = ?', (float)$data['filter_web_profit2']);
			}
		}
		
		if(isset($data['filter_items']) && $data['filter_items']) {
			$data['filter_items'] = html_entity_decode($data['filter_items'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_items'], '<>') === 0) {
				$query->where('u.items != ?', (float)substr($data['filter_items'], 2));
			} elseif(strpos($data['filter_items'], '>') === 0) {
				$query->where('u.items > ?', (float)substr($data['filter_items'], 1));
			} elseif(strpos($data['filter_items'], '<') === 0) {
				$query->where('u.items < ?', (float)substr($data['filter_items'], 1));
			} else {
				$query->where('u.items = ?', (float)$data['filter_items']);
			}
		}
		
		if(isset($data['filter_referals']) && $data['filter_referals']) {
			$data['filter_referals'] = html_entity_decode($data['filter_referals'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_referals'], '<>') === 0) {
				$query->where('u.referals != ?', (float)substr($data['filter_referals'], 2));
			} elseif(strpos($data['filter_referals'], '>') === 0) {
				$query->where('u.referals > ?', (float)substr($data['filter_referals'], 1));
			} elseif(strpos($data['filter_referals'], '<') === 0) {
				$query->where('u.referals < ?', (float)substr($data['filter_referals'], 1));
			} else {
				$query->where('u.referals = ?', (float)$data['filter_referals']);
			}
		}
		
		if(isset($data['filter_referal_money']) && $data['filter_referal_money']) {
			$data['filter_referal_money'] = html_entity_decode($data['filter_referal_money'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_referal_money'], '<>') === 0) {
				$query->where('u.referal_money != ?', (float)substr($data['filter_referal_money'], 2));
			} elseif(strpos($data['filter_referal_money'], '>') === 0) {
				$query->where('u.referal_money > ?', (float)substr($data['filter_referal_money'], 1));
			} elseif(strpos($data['filter_referal_money'], '<') === 0) {
				$query->where('u.referal_money < ?', (float)substr($data['filter_referal_money'], 1));
			} else {
				$query->where('u.referal_money = ?', (float)$data['filter_referal_money']);
			}
		}
		
		if(isset($data['filter_featured_author']) && in_array($data['filter_featured_author'], array('true','false'))) {
			$query->where('u.featured_author = ?', $data['filter_featured_author']);
		}
		
		return $db->fetchOne($query);
	}
	
	public static function getUsersCount($whereQuery='') {
		$db = JO_Db::getDefaultAdapter();
		
		if($whereQuery != '') {
			$whereQuery = " WHERE ".$whereQuery;
		}
		
		return $db->query("
			SELECT COUNT(user_id) AS count
			FROM ".Model_Users::getPrefixDB()."`users`
			$whereQuery
		")->fetchColumn();
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
			(SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) AS web_profit,
			(SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) AS referral_sum,
			( (SELECT SUM(price) - SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE owner_id = u.user_id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) ) AS web_profit2
			FROM ".Model_Users::getPrefixDB()."`users` u
			$whereQuery $order
			LIMIT $start, $limit
		")->fetchAll();
	}
	
	public function checkLogin($username, $password) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from(Model_Users::getPrefixDB().'users')
							->where('username = ?', (string)$username)
							->where('password = ?', (string)md5(md5($password)))
							->limit(1,0);
		$user_data = $db->fetchRow($query);
		
		if($user_data) {
			$groups = unserialize($user_data['groups']);
	    	if(is_array($groups) && count($groups) > 0) {
//	    		unset($user_data['groups']);
	    		$query_group = $db->select()
	    							->from(Model_Users::getPrefixDB().'user_groups')
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
		}
    	
		return $user_data;
	}
	
	public static function getStatistic($id) {
		$db = JO_Db::getDefaultAdapter();	

		$return = array();
		
#DEPOSIT		
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'deposit', 'SUM(`deposit`)')
					->where('user_id = ?', (int)$id);

		$return['deposit'] = $db->fetchOne($query);
		
#BUYED ITEMS
		$query = $db->select()
					->from('orders')
					->join('items', 'orders.item_id = items.id', array('item_name' => 'name'))
					->where('orders.user_id = ?', (int)$id)
					->where("orders.paid = 'true'");
		
		$res = $db->fetchAll($query);
		$return['total'] = 0;
		$return['items'] = array();
		if($res) {
			foreach($res AS $row) {
				$return['items'][] = $row;
				$return['total'] += $row['price'];
			}
		}
		
		return $return;
	}
	
	public function getTotalReferals($user_id, $referal_id) {
		 $db = JO_Db::getDefaultAdapter();
		 
		 $query = $db->select()
		    ->from(Model_Users::getPrefixDB().'users_referals_count', new JO_Db_Expr('COUNT(id) as sum'))
		    ->where('user_id = ?', $user_id)
		    ->where('referal_id = ?', $referal_id)
		    ->group('referal_id')
		    ->limit(1,0);
		
		return $db->fetchOne($query);
	}
	
    ///////////// Balances
    
	public static function createBalance($data) {
		$db = JO_Db::getDefaultAdapter();	
		$db->insert(Model_Users::getPrefixDB().'deposit', array(
			'user_id' => (int)$data['id'],
			'deposit' => (int)$data['deposit'],
			'paid' => 'true',
			'datetime' => new JO_Db_Expr('NOW()'),
			'from_admin' => '1'
		));
		
		$bid = $db->lastInsertId();
		
		if($bid) {
			$db->update(Model_Users::getPrefixDB().'users', array(
				'deposit' => new JO_Db_Expr('deposit + ' . (int)$data['deposit']),
				'total' => new JO_Db_Expr('total + ' . (int)$data['deposit'])
			), array('user_id = ?' => (int)$data['id']));
			
			$translate = JO_Translate::getInstance();
			Model_History::add($translate->translate('[Add from admin] Deposit from') . ' ' . WM_Currency::format($data['deposit']), $bid, $data['id']);
			
		}
		
		return $bid;
		
	}
    
	public static function editeBalance($balance_id, $data) {
		$db = JO_Db::getDefaultAdapter();	
		
		$row = self::getBalance($balance_id);
		if(!$row) {
			return;
		} 
		
		$aff = $db->update(Model_Users::getPrefixDB().'deposit', array(
			'deposit' => (int)$data['deposit']
		), array('id = ?' => (int)$balance_id));
		
		if($aff) {
			$db->update(Model_Users::getPrefixDB().'users', array(
				'deposit' => new JO_Db_Expr('deposit + ' . (-(int)$row['deposit'] + (int)$data['deposit']) ),
				'total' => new JO_Db_Expr('total + ' . (-(int)$row['deposit'] + (int)$data['deposit']))
			), array('user_id = ?' => (int)$row['user_id']));
			
			$translate = JO_Translate::getInstance();
			Model_History::add($translate->translate('[Edit from admin] Deposit from') . ' ' . WM_Currency::format($row['deposit']) . ' ' . $translate->translate('to') . ' ' . WM_Currency::format($data['deposit']), $balance_id, $row['user_id']);
			
		}
		
		return $balance_id;
		
	}
    
	public static function deleteBalance($balance_id) {
		$db = JO_Db::getDefaultAdapter();	
		
		$row = self::getBalance($balance_id);
		if(!$row) {
			return;
		}
		
		$aff = $db->delete('deposit', array('id = ?' => (int)$balance_id));
		
		if($aff) {
			$db->update(Model_Users::getPrefixDB().'users', array(
				'deposit' => new JO_Db_Expr('deposit - ' . (int)$row['deposit'] ),
				'total' => new JO_Db_Expr('total - ' . (int)$row['deposit'])
			), array('user_id = ?' => (int)$row['user_id']));
			
			$translate = JO_Translate::getInstance();
			Model_History::add($translate->translate('[Delete from admin '.($row['paid'] == 'true' ? '"paid"' : '"not paid"').'] Deposit from') . ' ' . WM_Currency::format($row['deposit']), $balance_id, $row['user_id']);
			
			
		}
		
		return $balance_id;
		
	}
	
    public static function getBalances($user_id) {
    	$db = JO_Db::getDefaultAdapter();	
	
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'deposit')
					->where('user_id = ?', (int)$user_id)
					->order('id DESC');
					
		return $db->fetchAll($query);
    }
    
    public static function getBalance($id) {
    	$db = JO_Db::getDefaultAdapter();	
	
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'deposit')
					->where('id = ?', (int)$id);
					
		return $db->fetchRow($query);
    }
	
    ///////////// Withdraws
    
	public static function getWithdraws($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from(Model_Users::getPrefixDB().'withdraw')
					->join(Model_Users::getPrefixDB().'users', 'withdraw.user_id = users.user_id', array('username','earning'))
					->order('withdraw.id DESC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalWithdraws($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from(Model_Users::getPrefixDB().'withdraw', 'COUNT(id)')
					->join(Model_Users::getPrefixDB().'users', 'withdraw.user_id = users.user_id', array());
		
		return $db->fetchOne($query);
	}
    
	public static function getWithdraw($id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from(Model_Users::getPrefixDB().'withdraw')
					->join(Model_Users::getPrefixDB().'users', 'withdraw.user_id = users.user_id', array('username','earning'))
					->where('withdraw.id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	public static function deleteWithdraw($id) {
		
		$info = self::getWithdraw($id);
		if($info && $info['paid'] == 'true') {
			return false;
		}
		
		$db = JO_Db::getDefaultAdapter();
		$db->delete(Model_Users::getPrefixDB().'withdraw', array('id = ?' => (int)$id));
	}
    
	public static function editeWithdraw($id, $data) {
		
		$win = self::getWithdraw($id);
		if(!$win) {
			return false;
		}
		
		$user = self::getUser($win['user_id']);
		
		if(!$user) {
			return false;
		}
		
		$db = JO_Db::getDefaultAdapter();
		
		if($data['earning'] > $user['earning']) {
			return false;
		}
		
		$db->update(Model_Users::getPrefixDB().'users', array(
			'earning' => new JO_Db_Expr('earning - ' . (float)$data['earning']),
			'total' => new JO_Db_Expr('total - ' . (float)$data['earning'])
		), array('user_id = ?' => $win['user_id']));
		
		$db->update(Model_Users::getPrefixDB().'withdraw', array(
			'paid' => 'true',
			'paid_datetime' => new JO_Db_Expr('NOW()')
		), array('id = ?' => (int)$id));
		
		return true;
	}
	
	public static function changeAuthor($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('users', array(
			'featured_author' => new JO_Db_Expr("IF(featured_author = 'true', 'false', 'true')")
		), array('user_id = ?' => (int)$id));
	}
}

?>