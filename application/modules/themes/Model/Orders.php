<?php
class Model_Orders {
    public function getAll () {
			$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders');
					
		return $db->fetchAll($query);	
    }
    
    public static function getAll2 ($where='', $order='`paid_datetime` DESC') {
			$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders')
					->order($order);
					
					if($where) {
						$query->where($where);
					}
		return $db->fetchAll($query);	
    }
    
	public static function getAllUserOrders($start, $limit = false, $group = false) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(array('o' => 'orders'), array('*', 'oid' => 'o.id', new JO_Db_Expr("(SELECT rate FROM items_rates WHERE item_id = i.id AND user_id = '" . JO_Session::get('user_id') . "' LIMIT 1) AS `getrate`"), new JO_Db_Expr("(SELECT COUNT(id) FROM items_rates WHERE item_id = i.id AND user_id = '" . JO_Session::get('user_id') . "') AS `hasrate`")))
					->joinLeft(array('i' => 'items'), 'o.item_id = i.id')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = i.user_id', array('username','avatar'))
					->where('o.user_id = ?', (int)JO_Session::get('user_id'))
					->where('type = ?', 'buy')
					->where('paid = ?', 'true')
					->order('o.id DESC');
		if($limit) {
			$query->limit($limit, $start);
		}
					
		if($group) {
			$query->group($group);
		}
					
		return $db->fetchAll($query);	
    }
    
	public static function getTotalAllUserOrders() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
						->from(array('o' => 'orders'), 'COUNT(o.id)')
						->joinLeft(array('i' => 'items'), 'o.item_id = i.id')
						->where('o.user_id = ?', (int)JO_Session::get('user_id'))
						->where('type = ?', 'buy')
						->where('paid = ?', 'true')
						->limit(1);
		return $db->fetchOne($query);	
    }
    
    public static function get($id) {
			$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders')
					->where('id = ?', (int)$id);
					
		return $db->fetchRow($query);	
	}
	
    
    
    public function isBuyed($item_id, $user_id, $oid = 0) {
        	$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders')
					->where('item_id = ?', (int)$item_id)
					->where('user_id = ?', (int)$user_id)
					->where("paid = 'true'")
					->limit(1);
					if((int)$oid) {
						$query->where('id = ?', (int)$oid);
					}
        return $db->fetchRow($query);	
    }
    
    public function add($data, $extended = false) {
        $db = JO_Db::getDefaultAdapter();
        $currency = WM_Currency::getCurrency();

        $query = $db->insert('orders',array(
	        'user_id'   => (int)JO_Session::get('user_id'),
	        'owner_id'	=> (int)$data['user_id'],
	        'item_id'	=> (int)$data['id'],
	        'item_name'	=> $data['name'],
	        'price'     => (!$extended ? (float)$data['price'] : (float)$data['extended_price']),
	        'datetime'	=> new JO_Db_Expr('NOW()'),
	        'extended'	=> (!$extended ? 'false' : 'true'),
        	'type'		=> 'buy',
        	'currency_code' => $currency['code'],
        	'currency_value' => $currency['value'],
        	'module' => 'themes'
        ));
		
		return $db->lastInsertId();
	}
	
    public function IsPay($order_id) {
		$row = Model_Orders::get($order_id);
		if(!is_array($row)) {
			return false;
		}
		return $row['paid'] == 'true' ? true : false;
	}
	
	public function orderIsPay($order_id) {
		$row = Model_Orders::get($order_id);
		
		if(!is_array($row)) {
			return; //ERROR
		}	
					
		$user = Model_Users::getUser($row['owner_id']);
					
					
		$percent = Model_Percentes::getPercentRow($user);
		$percent = $percent['percent'];
					
		$receiveMoney = floatval($row['price']) * floatval($percent) / 100;

		
		$db = JO_Db::getDefaultAdapter();
		$db->update('orders', array(
			'paid_datetime'	=> new JO_Db_Expr('NOW()'),
			'receive'	=> $receiveMoney,
		), array('id = ?' => (int)$order_id));
		
		$db->update(Model_Users::getPrefixDB().'users', array(
			'earning'	=>  new JO_Db_Expr('earning+'.$receiveMoney),
			'total'	=> new JO_Db_Expr('total+'.$receiveMoney),
			'sold'	=> new JO_Db_Expr('sold+'.$row['price']),
			'sales'	=> new JO_Db_Expr('sales+1')
		), array('user_id = ?' => $row['owner_id']));

					
		$you = Model_Users::getUser($row['user_id']);

		#CHECK REFERAL					
		if($you['referal_id'] != '0') {
			$row['order_id'] = $order_id;
			self::referalMoney($row, $you);
						
		}

		$db->update(Model_Users::getPrefixDB().'users', array(
			'buy'	=> new JO_Db_Expr('buy+1')
		), array('user_id = ?' => $row['user_id']));
					
		$toinsert = array('sales'=>new JO_Db_Expr('sales+1'), 'earning'=> new JO_Db_Expr('earning+'.$row['price']));
		#UPDATE ITEM
		if($row['extended'] == 'true') {
		    $db->update('users', array(
				'items'	=> new JO_Db_Expr('items-1')
		    ), array('user_id = ?' => $row['owner_id']));
			$toinsert['status'] = 'extended_buy';
		}

		
		 $db->update('items', $toinsert, array('id = ?' => $row['item_id']));
		 
		return true;
	}
	
	public function referalMoney($row, $you) {
	    $db = JO_Db::getDefaultAdapter();
		
		$totals = Model_Users::getTotalReferals($you['user_id'], $you['referal_id']);
		
		if((int)JO_Registry::get('referal_sum') && ($totals+1) > (int)JO_Registry::get('referal_sum')) {
		    
			$db->update(Model_Users::getPrefixDB().'users', array(
				'referal_id'	=> '0'
			), array('user_id = ?' => $you['user_id']));
		 
			return false;
		}
		
		$referalMoney = floatval($row['price']) * ((int)str_replace('%', '', JO_Registry::forceGet('referal_percent')) / 100);

		$db->update(Model_Users::getPrefixDB().'users', array(
			'earning'	=> new JO_Db_Expr('earning+'.$referalMoney),
			'total'		=> new JO_Db_Expr('total+'.$referalMoney),
			'referal_money'	=> new JO_Db_Expr('referal_money'+$referalMoney)
		), array('user_id = ?' => $you['referal_id']));
		 
		
	    $db->insert('orders', array(
			'order_id'	=> $row['order_id'],
			'user_id'	=> $row['user_id'],
		    'owner_id'	=> $you['referal_id'],
		    'item_id'	=> $row['item_id'],
		    'item_name'	=> $row['item_name'],
		    'price'		=> $row['price'],
		    'datetime'	=> new JO_Db_Expr('NOW()'),
		    'receive'	=> $referalMoney,
		    'paid'		=> 'true',
		    'paid_datetime'	=> new JO_Db_Expr('NOW()'),
		    'type'		=> 'referal',
        	'currency_code' => $row['currency_code'],
        	'currency_value' => $row['currency_value']
		));
		
		 $db->insert(Model_Users::getPrefixDB().'users_referals_count', array(
			'user_id'	=> $you['user_id'],
		    'referal_id'	=> $you['referal_id'],
		    'referal_sum' => $referalMoney,
		    'datetime'	=> new JO_Db_Expr('NOW()'),
		    'order_type' => 'sale'
		));
		
		
	}
	
	public function buy($item, $price, $extended='false') {
        		
		$you = Model_Users::getUser(JO_Session::get('user_id'));
		
		$deposit = 0;
		$earning = 0;
		if($you['deposit'] > $price) {
			$deposit = $price;
		}
		else {
			$deposit = $you['deposit'];
			$earning = floatval($price) - floatval($you['deposit']);
		}
		
        $db = JO_Db::getDefaultAdapter();
        $db->update(Model_Users::getPrefixDB().'users', array(
        	'deposit' => new JO_Db_Expr('deposit - '.floatval($deposit)),
			'earning' => new JO_Db_Expr('earning - '.floatval($earning)),
			'total' => new JO_Db_Expr('total - '.floatval($price))
        ), array('user_id =?'=> $you['user_id']));    
		
		JO_Session::set('deposit', floatval(JO_Session::get('deposit')) - floatval($deposit));
		JO_Session::set('earning', floatval(JO_Session::get('earning')) - floatval($earning));
		JO_Session::set('total', floatval(JO_Session::get('total')) - floatval($price));
		
#ADD PRICE TO OWNER USER
		$user = Model_Users::getUser($item['user_id']);
		
	
		$percent = Model_Percentes::getPercentRow($user);
		$percent = $percent['percent'];
		
		$receiveMoney = floatval($price) * floatval($percent) / 100;

		$db->update(Model_Users::getPrefixDB().'users', array(
        	'earning' => new JO_Db_Expr('earning+'.floatval($receiveMoney)),
			'total' => new JO_Db_Expr('total + '.floatval($receiveMoney)),
			'sold' => new JO_Db_Expr('sold + '.floatval($price)),
            'sales'	=> new JO_Db_Expr('sales+1')
        ), array('user_id =?'=> $user['user_id']));    
		
		
#ADD ORDER

        $currency = WM_Currency::getCurrency();
        
        $db->insert('orders', array(
	        'user_id'   => (int)JO_Session::get('user_id'),
	        'owner_id'	=> (int)$item['user_id'],
	        'item_id'	=> (int)$item['id'],
	        'item_name'	=> $item['name'],
	        'price'		=> (float)$price,
	        'datetime'	=> new JO_Db_Expr('NOW()'),
	        'receive'	=> $receiveMoney,
	        'paid'	    => 'true',
	        'paid_datetime'	=> new JO_Db_Expr('NOW()'),
        	'extended' => $extended,
        	'type' => 'buy',
        	'currency_code' => $currency['code'],
        	'currency_value' => $currency['value'],
        	'module' => $item['module']
        ));    
        
        $order_id = $db->lastInsertId();
	
		if($order_id && $you['referal_id'] != '0') {
			
			self::referalMoney(array(
				'order_id' => $order_id,
				'price' => $price,
				'user_id' => JO_Session::get('user_id'),
				'owner_id' => $item['user_id'],
				'item_id' => $item['id'],
				'item_name' => $item['name'],
	        	'currency_code' => $currency['code'],
	        	'currency_value' => $currency['value']
			), $you);
			
		}
        
		$db->update(Model_Users::getPrefixDB().'users', array('buy'=>new JO_Db_Expr('buy+1')), array('user_id =?'=>JO_Session::get('user_id')));
		
	
#UPDATE ITEM
		$toadd = array('sales'=>new JO_Db_Expr('sales+1'), 'earning'=>new JO_Db_Expr('earning+'.$price));
		if($extended == 'true') {
			$toadd['status'] = 'extended_buy';
		}
		$db->update('items', $toadd, array('id = ?'=>$item['id']));	

		return true;
	}
	
	/* by joro */
	
	public static function getWeekStats($user_id = 0) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$buff = 6 - date('N');	
		$lastWeekDay = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') - $buff - date('N')), date('Y')));
		$firstWeekDay = date('Y-m-d', mktime(0, 0, 0, date('m'), (date('d') + $buff + 2), date('Y')));
		
		$query = $db->select()
					->from('orders', array('sold' => 'COUNT(id)', 'earning' => 'SUM(receive)'))
					->where('owner_id = ?', (int)$user_id)
					->where("`paid` = 'true'")
					->where('`datetime` > ?', $lastWeekDay)
					->where('`datetime` < ?', $firstWeekDay);
		
		$results = $db->fetchRow($query);
		
		$weekStats = array('earning' => 0, 'sold' => 0);
		
		if($results) {
			$weekStats = $results;
		}
		
		return $weekStats;
	}
	
    public function getStatement($userID) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = "SELECT *
				FROM
					(SELECT `user_id`, `price`, `receive`, `paid_datetime` AS `datetime`, `item_name`, 
						'order' AS `type`, DATE_FORMAT(`paid_datetime`, '%M %Y') AS month, 
						DATE_FORMAT(`paid_datetime`, '%d %M') AS day 
					FROM `orders` 
					WHERE (`owner_id` = '".intval($userID)."' AND type='buy' OR `user_id` = '".intval($userID)."') 
						AND `paid` = 'true') t1
					UNION 
					(SELECT '' AS user_id, `deposit` AS `price`, '' AS `receive`, `datetime`, '' AS `item_name`, 
						'deposit' AS `type`, DATE_FORMAT(`datetime`, '%M %Y') AS month, 
						DATE_FORMAT(`datetime`, '%d %M') AS day  
					FROM `deposit`
					WHERE `user_id` = '".intval($userID)."' AND `paid` = 'true')
					UNION 
					(SELECT '' AS user_id, `amount` AS `price`, '' AS `receive`, `datetime`, '' AS `item_name`, 
						'withdraw' AS `type`, DATE_FORMAT(`datetime`, '%M %Y') AS month, 
						DATE_FORMAT(`datetime`, '%d %M') AS day  
					FROM `withdraw` 
					WHERE `user_id` = '".intval($userID)."' 
						AND `paid` = 'true')
					UNION 
					(SELECT '' AS user_id, `referal_sum` AS `price`, '' AS `receive`, `datetime`, IF(`order_type` = 'deposit', 1, 2) AS `item_name`, 
						'referrals' AS `type`, DATE_FORMAT(`datetime`, '%M %Y') AS month, 
						DATE_FORMAT(`datetime`, '%d %M') AS day  
					FROM `users_referals_count` 
					WHERE `referal_id` = '".intval($userID)."' AND order_type NOT IN ('register', 'gast'))
					UNION
					(SELECT user_id, `amount` AS `price`, '' AS `receive`, `datetime`, '' AS `item_name`,
						'membership' AS `type`,  DATE_FORMAT(`datetime`, '%M %Y') AS month, 
						DATE_FORMAT(`datetime`, '%d %M') AS day
					FROM `membership`
					WHERE `user_id` = '". intval($userID) ."')
				ORDER BY `datetime` ASC";
      	
		return $db->fetchAll($query);
	}
	
	public static function getEarnings($user_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
				->from('users_referals_count', array(new JO_Db_Expr('
					SUM(IF(order_type = \'register\', 1, 0)) AS register,
					SUM(IF(order_type = \'gast\', 1, 0)) AS gast,
					SUM(IF(order_type = \'deposit\', 1, 0)) AS deposit,
					SUM(IF(order_type = \'sale\', referal_sum, 0)) AS sales,
					DATE_FORMAT(`datetime`, \'%M %Y\') AS date
				')))
				->where('referal_id = ?', (int) $user_id)
				->group(new JO_Db_Expr('DATE_FORMAT(`datetime`, \'%Y%m\')'))
				->order(new JO_Db_Expr('DATE_FORMAT(`datetime`, \'%Y%m\') DESC'));
      	
		return $db->fetchAll($query);
	}
	
	public static function getSalesByUser($user_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('orders', array(new JO_Db_Expr('
						SUM(receive) AS earnings,
						COUNT(id) AS sales,
						DATE_FORMAT(paid_datetime, \'%M %Y\') AS month,
						DATE_FORMAT(paid_datetime, \'%d %M\') AS day
					')))
					->where('owner_id = ?', (int) $user_id)
					->where('type = ?', 'buy')
					->where('paid = ?', new JO_Db_Expr('true'))
					->group(new JO_Db_Expr('DATE_FORMAT(paid_datetime, \'%Y%m%d\')'))
					->order(new JO_Db_Expr('DATE_FORMAT(paid_datetime, \'%Y%m%d\') ASC'));
				
		return $db->fetchAll($query);
	}
	
	public static function getBayers($item_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = "SELECT user_id
				FROM `orders` 
				WHERE `item_id` = '". (int) $item_id ."'
					AND `paid` = 'true'
				GROUP BY `user_id`";
				
		return $db->fetchCol($query);
	}
	
	public static function getClients($user_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = "SELECT user_id
				FROM `orders` 
				WHERE `owner_id` = '". (int) $user_id ."'
					AND `paid` = 'true'
				GROUP BY `user_id`";
				
		return $db->fetchCol($query);
	}
	
	public static function delete($order_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'DELETE FROM orders WHERE id = \''. (int)$order_id .'\' AND paid = \'false\'';
		
		$db->query($query);
	}
	
	public static function update($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'UPDATE orders SET 
				`paid` = \''. $data['paid'] .'\',
				`order_status_id` = \''. (int)$data['order_status_id'] .'\',
				`paid_from` = \''. $data['paid_from'] .'\'
				WHERE id = \''. (int)$id .'\'';
				
		$db->query($query);
	}
}