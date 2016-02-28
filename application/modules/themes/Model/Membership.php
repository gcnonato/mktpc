<?php

class Model_Membership {
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		
        $query = 'SELECT ms.*, md.description FROM membership_sums ms 
        		LEFT JOIN membership_description md ON md.id = ms.id 
        		WHERE ms.status = \'true\'
        			AND md.lid =\''. (int)JO_Registry::get('config_language_id') .'\'
        		ORDER BY ms.order_index';	
		
		return $db->fetchAll($query);	
	}
	
	public static function get($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT * FROM membership WHERE id = \''. (int)$id .'\'';
		
		return $db->fetchRow($query);
	}
	
	public static function add($amount) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'INSERT INTO membership (`max_downloads_cnt`, `datetime`, `amount`, `user_id`) 
				SELECT max_items_cnt, NOW() AS datetime, price, \''. JO_Session::get('user_id') .'\' 
				FROM membership_sums
				WHERE price = \''. (float)$amount .'\'
					AND status = \'true\'';
					
		$db->query($query);
		
		return $db->lastInsertId();
	}
	
	public static function update($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'UPDATE membership SET 
				`paid` = \''. $data['paid'] .'\',
				`order_status_id` = \''. (int)$data['order_status_id'] .'\',
				`paid_from` = \''. $data['paid_from'] .'\'
				WHERE id = \''. (int)$id .'\'';
				
		$db->query($query);
	}
	
	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->delete('membership', array('id = ?' => (int)$id, 'paid = ?' => 'false'));
	}
	
	public static function membershipIsPay($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$order = self::get($id);
		
		$more = 'SELECT id, (max_downloads_cnt - downloads) AS downloads FROM membership 
				WHERE `user_id` = \''. JO_Session::get('user_id') .'\'
					AND id <> \''. (int)$id .'\'
					AND DATE(datetime) > SUBDATE(NOW(), interval 1 MONTH)
					AND paid = \'true\'
					AND valid = \'true\'';
		
		$results = $db->fetchRow($more);
		$moreQuery = '';
		if($results) {
			if($order['max_downloads_cnt'] > 0) {
				$moreQuery = ', max_downloads_cnt = max_downloads_cnt + '. max($results['downloads'], 0);
			}
						
			$db->query('UPDATE membership SET valid = \'false\' WHERE id = \''. $results['id'] .'\'');
		}
		
		$query = 'UPDATE membership 
					SET `paid` = \'true\''. $moreQuery .', `added` =\'true\'
				WHERE `id` = \''. (int)$id .'\'';
		
		$db->query($query);
	}
	
	public static function getByUser($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT * FROM membership
				WHERE user_id = '. (int)$id .'
					AND DATE(datetime) > SUBDATE(NOW(), interval 1 MONTH)
					AND paid = \'true\'
					AND (max_downloads_cnt = 0 OR (max_downloads_cnt - downloads) > 0)';
					
		return $db->fetchRow($query);
	}
	
	public static function buy($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'UPDATE membership SET downloads = downloads + 1 
				WHERE user_id = \''. (int)$id .'\'
					AND DATE(datetime) > SUBDATE(NOW(), interval 1 MONTH)
					AND paid = \'true\'';
					
		$db->query($query);
	}
}
?>