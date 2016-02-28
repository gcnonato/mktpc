<?php

class Model_Orders {

	public static function getSalesStatus($whereQuery='', $type='buy') {
		$db = JO_Db::getDefaultAdapter();

		return $db->query("
			SELECT COUNT(`id`) as `num`, SUM(`price`) as `total`, SUM(`receive`) AS `receive` 
			FROM `orders` 
			WHERE `type` = '".($type)."' AND `paid` = 'true' $whereQuery 
			GROUP BY `type`
		")->fetch();
		
	}
	
	public static function getSalesStatusByDay($whereQuery='', $type='buy') {
		$db = JO_Db::getDefaultAdapter();
		
		$res = $db->query("
			SELECT COUNT(`id`) as `num`, SUM(`price`) as `total`, SUM(`receive`) AS `receive` , DATE(`datetime`) AS `date`
			FROM `orders` 
			WHERE `type` = '".$type."' AND `paid` = 'true' $whereQuery 
			GROUP BY DATE(`datetime`)
			ORDER BY DATE(`datetime`) ASC
		")->fetchAll();
		
		
		$data = array();
		
		foreach($res AS $d) {
			$data[$d['date']] = $d;
		}
		
		return $data;
	}
	
	public static function getAll($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(array('o' => 'orders'), array('o.*', new JO_Db_Expr('(o.price - o.receive) AS web_profit, (SELECT receive FROM orders WHERE order_id = o.id LIMIT 1) AS referral_sum, ((o.price - o.receive) - (SELECT receive FROM orders WHERE order_id = o.id LIMIT 1)) AS web_profit2')))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'o.user_id = u.user_id', array('username'))
					->joinLeft(array('u2' => Model_Users::getPrefixDB().'users'), 'o.owner_id = u2.user_id', array('owner' => 'username'));
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
		
		$allow_sort = array(
			'o.id',
			'o.item_name',
			'o.item_id',
			'u.username',
			'u2.username',
			'o.price',
			'o.receive',
			'o.datetime',
			'o.paid',
			'o.paid_datetime',
			'o.extended',
			'o.type',
			'web_profit',
			'web_profit2'
		); 
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('o.id' . $sort);
		}

		////////////filter
		
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('o.id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filter_order_id']) && $data['filter_order_id']) {
			$query->where('o.order_id = ?', (int)$data['filter_order_id']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('o.item_name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_owner']) && $data['filter_owner']) {
			$query->where('u2.username LIKE ?', '%' . $data['filter_owner'] . '%');
		}
		
		if(isset($data['filter_price']) && $data['filter_price']) {
			$data['filter_price'] = html_entity_decode($data['filter_price'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_price'], '<>') === 0) {
				$query->where('o.price != ?', (float)substr($data['filter_price'], 2));
			} elseif(strpos($data['filter_price'], '>') === 0) {
				$query->where('o.price > ?', (float)substr($data['filter_price'], 1));
			} elseif(strpos($data['filter_price'], '<') === 0) {
				$query->where('o.price < ?', (float)substr($data['filter_price'], 1));
			} else {
				$query->where('o.price = ?', (float)$data['filter_price']);
			}
		}
		
		if(isset($data['filter_receive']) && $data['filter_receive']) {
			$data['filter_receive'] = html_entity_decode($data['filter_receive'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_receive'], '<>') === 0) {
				$query->where('(o.price - o.receive) != ?', (float)substr($data['filter_receive'], 2));
			} elseif(strpos($data['filter_receive'], '>') === 0) {
				$query->where('(o.price - o.receive) > ?', (float)substr($data['filter_receive'], 1));
			} elseif(strpos($data['filter_receive'], '<') === 0) {
				$query->where('(o.price - o.receive) < ?', (float)substr($data['filter_receive'], 1));
			} else {
				$query->where('(o.price - o.receive) = ?', (float)$data['filter_receive']);
			}
		}
		
		if(isset($data['filter_web_receive']) && $data['filter_web_receive']) {
			$data['filter_web_receive'] = html_entity_decode($data['filter_web_receive'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_web_receive'], '<>') === 0) {
				$query->where('web_profit2 != ?', (float)substr($data['filter_web_receive'], 2));
			} elseif(strpos($data['filter_web_receive'], '>') === 0) {
				$query->where('web_profit2 > ?', (float)substr($data['filter_web_receive'], 1));
			} elseif(strpos($data['filter_web_receive'], '<') === 0) {
				$query->where('web_profit2 < ?', (float)substr($data['filter_web_receive'], 1));
			} else {
				$query->where('web_profit2 = ?', (float)$data['filter_web_receive']);
			}
		}
		
		if(isset($data['filter_item_id']) && $data['filter_item_id']) {
			$query->where('o.item_id = ?', (int)$data['filter_item_id']);
		}
		
		if(isset($data['filter_paid']) && in_array($data['filter_paid'], array('true','false'))) {
			$query->where('o.paid = ?', $data['filter_paid']);
		}
		
		if(isset($data['filter_extended']) && in_array($data['filter_extended'], array('true','false'))) {
			$query->where('o.extended = ?', $data['filter_extended']);
		}
		
		if(isset($data['filter_type']) && $data['filter_type']) {
			$query->where('o.type = ?', $data['filter_type']);
		}
		
		if(isset($data['filter_from']) && JO_Date::dateToUnix($data['filter_from'])) {
			$query->where('DATE(o.datetime) >= ?', JO_Date::getInstance($data['filter_from'], 'yy-mm-dd', true)->toString());
		}
		
		if(isset($data['filter_to']) && JO_Date::dateToUnix($data['filter_to'])) {
			$query->where('DATE(o.datetime) <= ?', JO_Date::getInstance($data['filter_to'], 'yy-mm-dd', true)->toString());
		}
		
		if(isset($data['filter_paid_from']) && JO_Date::dateToUnix($data['filter_paid_from'])) {
			$query->where('DATE(o.paid_datetime) >= ?', JO_Date::getInstance($data['filter_paid_from'], 'yy-mm-dd', true)->toString());
		}
		
		if(isset($data['filter_paid_to']) && JO_Date::dateToUnix($data['filter_paid_to'])) {
			$query->where('DATE(o.paid_datetime) <= ?', JO_Date::getInstance($data['filter_paid_to'], 'yy-mm-dd', true)->toString());
		}
	
		return $db->fetchAll($query);
		
	}
	
	public static function getTotal($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(array('o' => 'orders'), new JO_Db_Expr('COUNT(o.id), (o.price - o.receive) AS web_profit, (SELECT receive FROM orders WHERE order_id = o.id LIMIT 1) AS referral_sum, ((o.price - o.receive) - (SELECT receive FROM orders WHERE order_id = o.id LIMIT 1)) AS web_profit2'))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'o.user_id = u.user_id', array())
					->joinLeft(array('u2' => Model_Users::getPrefixDB().'users'), 'o.owner_id = u2.user_id', array());
		
		////////////filter
		
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('o.id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filter_order_id']) && $data['filter_order_id']) {
			$query->where('o.order_id = ?', (int)$data['filter_order_id']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('o.item_name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_owner']) && $data['filter_owner']) {
			$query->where('u2.username LIKE ?', '%' . $data['filter_owner'] . '%');
		}
		
		if(isset($data['filter_price']) && $data['filter_price']) {
			$data['filter_price'] = html_entity_decode($data['filter_price'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_price'], '<>') === 0) {
				$query->where('o.price != ?', (float)substr($data['filter_price'], 2));
			} elseif(strpos($data['filter_price'], '>') === 0) {
				$query->where('o.price > ?', (float)substr($data['filter_price'], 1));
			} elseif(strpos($data['filter_price'], '<') === 0) {
				$query->where('o.price < ?', (float)substr($data['filter_price'], 1));
			} else {
				$query->where('o.price = ?', (float)$data['filter_price']);
			}
		}
		
		if(isset($data['filter_receive']) && $data['filter_receive']) {
			$data['filter_receive'] = html_entity_decode($data['filter_receive'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_receive'], '<>') === 0) {
				$query->where('o.receive != ?', (float)substr($data['filter_receive'], 2));
			} elseif(strpos($data['filter_receive'], '>') === 0) {
				$query->where('o.receive > ?', (float)substr($data['filter_receive'], 1));
			} elseif(strpos($data['filter_receive'], '<') === 0) {
				$query->where('o.receive < ?', (float)substr($data['filter_receive'], 1));
			} else {
				$query->where('o.receive = ?', (float)$data['filter_receive']);
			}
		}
		
		if(isset($data['filter_web_receive']) && $data['filter_web_receive']) {
			$data['filter_web_receive'] = html_entity_decode($data['filter_web_receive'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_web_receive'], '<>') === 0) {
				$query->where('web_profit2 != ?', (float)substr($data['filter_web_receive'], 2));
			} elseif(strpos($data['filter_web_receive'], '>') === 0) {
				$query->where('web_profit2 > ?', (float)substr($data['filter_web_receive'], 1));
			} elseif(strpos($data['filter_web_receive'], '<') === 0) {
				$query->where('web_profit2 < ?', (float)substr($data['filter_web_receive'], 1));
			} else {
				$query->where('web_profit2 = ?', (float)$data['filter_web_receive']);
			}
		}
		
		if(isset($data['filter_item_id']) && $data['filter_item_id']) {
			$query->where('o.item_id = ?', (int)$data['filter_item_id']);
		}
		
		if(isset($data['filter_paid']) && in_array($data['filter_paid'], array('true','false'))) {
			$query->where('o.paid = ?', $data['filter_paid']);
		}
		
		if(isset($data['filter_extended']) && in_array($data['filter_extended'], array('true','false'))) {
			$query->where('o.extended = ?', $data['filter_extended']);
		}
		
		if(isset($data['filter_type']) && $data['filter_type']) {
			$query->where('o.type = ?', $data['filter_type']);
		}
		
		if(isset($data['filter_from']) && JO_Date::dateToUnix($data['filter_from'])) {
			$query->where('DATE(o.datetime) >= ?', JO_Date::getInstance($data['filter_from'], 'yy-mm-dd', true)->toString());
		}
		
		if(isset($data['filter_to']) && JO_Date::dateToUnix($data['filter_to'])) {
			$query->where('DATE(o.datetime) <= ?', JO_Date::getInstance($data['filter_to'], 'yy-mm-dd', true)->toString());
		}
		
		if(isset($data['filter_paid_from']) && JO_Date::dateToUnix($data['filter_paid_from'])) {
			$query->where('DATE(o.paid_datetime) >= ?', JO_Date::getInstance($data['filter_paid_from'], 'yy-mm-dd', true)->toString());
		}
		
		if(isset($data['filter_paid_to']) && JO_Date::dateToUnix($data['filter_paid_to'])) {
			$query->where('DATE(o.paid_datetime) <= ?', JO_Date::getInstance($data['filter_paid_to'], 'yy-mm-dd', true)->toString());
		}
		
		return $db->fetchOne($query);
		
	}
	
	//////
	
	public function delete($order_id) {
		$row = Model_Orders::get($order_id);
		if(!$row || $row['paid'] == 'true') {
			return false; //ERROR
		}
		
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('orders', array('id = ?' => (int)$order_id));
	}
	
	public static function deleteMulti($order_ids) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!is_array($order_ids)) return false;
		
		$query = 'DELETE FROM orders WHERE id IN ('. implode(',', $order_ids) .') AND paid = \'false\'';
		$db->query($query);
	}
	
    public static function get($id) {
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders')
					->where('id = ?', (int)$id);
					
		return $db->fetchRow($query);	
	}
	
    public static function getReferal($id) {
			$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders')
					->where('order_id = ?', (int)$id);
					
		return $db->fetchRow($query);	
	}
	
	public function changeStatus($order_id) {
		$row = Model_Orders::get($order_id);
		if(!$row || $row['paid'] == 'true') {
			return false; //ERROR
		}
					
		$percent = Model_Percents::getPercentRow($row['owner_id']);
		$percent = $percent['percent'];
					
		$receiveMoney = floatval($row['price']) * floatval($percent) / 100;

		
		$db = JO_Db::getDefaultAdapter();
		$db->update('orders', array(
			'paid'	=> 'true',
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
		if($you['referal_id'] != 0) {
			$row['order_id'] = $order_id;
			Model_Orders::referalMoney($row, $you);
						
		}

		$db->update(Model_Users::getPrefixDB().'users', array(
			'buy'	=> new JO_Db_Expr('buy+1')
		), array('user_id = ?' => $row['user_id']));
					
		$toinsert = array('sales'=>new JO_Db_Expr('sales+1'), 'earning'=> new JO_Db_Expr('earning+'.$row['price']));
		#UPDATE ITEM
		if($row['extended'] == 'true') {
		    $db->update('users', array(
			'items'	=> new JO_Db_Expr('items-1')
		    ), array('user_id = ?' => $row['user_id']));
			$toinsert['status'] = 'extended_buy';
		}

		
		 $db->update('items', $toinsert, array('id = ?' => $row['id']));
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
		
		$referalMoney = floatval($row['price']) * (int)JO_Registry::get('referal_percent') / 100;

		$db->update(Model_Users::getPrefixDB().'users', array(
			'earnings'	=> new JO_Db_Expr('earnings+'.$referalMoney),
			'total'		=> new JO_Db_Expr('total+'.$referalMoney),
			'referal_money'	=> new JO_Db_Expr('referal_money'+$referalMoney)
		), array('user_id = ?' => $you['user_id']));
		 
		
	    $db->insert('orders', array(
			'order_id'	=> $row['id'],
			'user_id'	=> $row['user_id'],
		    'owner_id'	=> $row['owner_id'],
		    'item_id'	=> $row['item_id'],
		    'item_name'	=> $row['item_name'],
		    'price'		=> $row['price'],
		    'datetime'	=> new JO_Db_Expr('NOW()'),
		    'receive'	=> $referalMoney,
		    'paid'		=> 'true',
		    'paid_datetime'	=> new JO_Db_Expr('NOW()'),
		    'type'		=> 'referal'
		));
		
		 $db->insert(Model_Users::getPrefixDB().'users_referals_count', array(
			'user_id'	=> $you['user_id'],
		    'referal_id'	=> $you['referal_id'],
		    'datetime'	=> new JO_Db_Expr('NOW()'),
		));
		
	}
	
}

?>