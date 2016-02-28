<?php

class Model_Membership {
	
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT ms.*, md.* FROM membership_description md
				JOIN membership_sums ms ON ms.id = md.id 
				WHERE md.lid = \''. JO_Registry::get('default_config_language_id') .'\'
				ORDER BY ms.order_index ASC';
			
        return $db->fetchAll($query);	
	}
	
	public static function get($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT ms.*, md.lid, md.description FROM membership_sums ms
				LEFT JOIN membership_description md ON md.id = ms.id
				WHERE ms.id = \''. (int)$id .'\'';
				
		return $db->fetchAll($query);
	}
	
	public static function getMaxPosition() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT (COUNT(id) + 1) AS max_position
				FROM membership_sums';
				
		$max = $db->fetchRow($query);
		
		return $max['max_position'];
	}
	
	public static function create($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->insert('membership_sums', array(
			'price' => (float)$data['price'], 
			'status' => $data['status'], 
			'order_index' => (int)$data['order_index'], 
			'max_items_cnt' => (int)$data['max_items_cnt']
		));
		$last_id = $db->lastInsertId();
		
		$query = 'INSERT INTO membership_description (`id`, `lid`, `description`) VALUES (';
		
		$description = array();
		foreach($data['description'] as $k => $v) {
			$description[] = $last_id .', \''. (int)$k .'\', \''. $v .'\'';
		}
		
		$query .= implode('), (', $description) .')';
		$db->query($query);
	}
	
	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('membership_sums', array(
			'price' => (float)$data['price'], 
			'status' => $data['status'], 
			'order_index' => (int)$data['order_index'], 
			'max_items_cnt' => (int)$data['max_items_cnt']
		), array('id = ?' => (int)$id));
		
		$db->delete('membership_description', array(
			'id = ?' => (int) $id
		));
		
		$query = 'INSERT INTO membership_description (`id`, `lid`, `description`) VALUES (';
		
		$description = array();
		foreach($data['description'] as $k => $v) {
			$description[] = (int)$id .', \''. (int)$k .'\', \''. $v .'\'';
		}
		
		$query .= implode('), (', $description) .')';
		$db->query($query);
	}
	
	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'DELETE ms, md FROM membership_sums AS ms 
				INNER JOIN membership_description AS md
				WHERE ms.id = md.id AND ms.id = \''. (int)$id .'\'';
		
		$db->query($query);
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('membership_sums', array(
			'status' => new JO_Db_Expr("IF(status = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
	public static function changeSortOrder($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('membership_sums', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$id));
	}
	
	public static function getNoPaid($data) {
		$db = JO_Db::getDefaultAdapter();    
        
		$where = '';
		if(isset($data['filter_id']) && !empty($data['filter_id'])) {
			$data['filter_id'] = preg_replace ( '#[^\d+]#i' , '' , $data['filter_id'] );
			if($data['filter_id'] != 0) {
				$where = ' AND m.id = '. $db->quote($data['filter_id']);
			}
		}
		
		if(isset($data['filter_username']) && !empty($data['filter_username'])) {
			$where .= ' AND u.username = '. $db->quote($data['filter_username']);
		}
		
		if(isset($data['filter_price']) && !empty($data['filter_price'])) {
			$data['filter_price'] = preg_replace ( '#[^\d+]#i' , '' , $data['filter_price'] );
			if($data['filter_price'] != 0) {
				$where .= ' AND m.amount = '. $db->quote($data['filter_price']);
			}
		}
		
        $query = 'SELECT m.*, u.username
        		FROM membership m
        		JOIN users u ON u.user_id = m.user_id
        		WHERE m.paid = \'false\' AND valid=\'true\''. $where .'
        		ORDER BY '. $data['order'] .' '. $data['sort'];
			
        return $db->fetchAll($query);
	}
	
	public static function delete_membership($id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('membership', array('id = ?' => (int)$id));
	}
	
	public static function change_membership($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$row = $db->fetchRow('SELECT * FROM membership WHERE id = '. $db->quote($id));
		
		if($row) {
			if($row['paid'] === 'true') {
				return;
			}
			
			$more = 'SELECT id, (max_downloads_cnt - downloads) AS downloads FROM membership 
				WHERE `user_id` = \''. $row['user_id'] .'\'
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
					WHERE `id` = \''. (int)$row['id'] .'\'';
			
			$db->query($query);
		}
	}

	public static function deleteMulti($order_ids) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!is_array($order_ids)) return false;
		
		$query = 'DELETE FROM membership WHERE id IN ('. implode(',', $order_ids) .') AND paid = \'false\' AND valid = \'true\'';
		$db->query($query);
	}
}
?>
