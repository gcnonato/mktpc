<?php

class Model_Forum {
	
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT *
				FROM forum
				ORDER BY order_index ASC';
			
        return $db->fetchAll($query);	
	}
	
	public static function get($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT *
				FROM forum
				WHERE id = \''. (int)$id .'\'';
			
        return $db->fetchRow($query);	
	}
	
	public static function getMaxPosition() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT (COUNT(id) + 1) AS max_position
				FROM forum';
				
		$max = $db->fetchRow($query);
		
		return $max['max_position'];
	}
	
	public static function create($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->insert('forum', array(
			'name' => $data['name'], 
			'status' => $data['status'], 
			'order_index' => self::getMaxPosition(),
		));
		
		return $db->lastInsertId();
	}
	
	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('forum', array(
			'name' => $data['name'], 
			'status' => $data['status']
		), array('id = ?' => (int)$id));
	}
	
	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'DELETE f, fc FROM forum AS f 
				INNER JOIN forum_comments AS fc
				WHERE f.id = fc.thread_id AND f.id = \''. (int)$id .'\'';
		
		$db->query($query);
	}
	
	public static function deleteComment($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'DELETE FROM forum_comments WHERE id = \''. (int)$id .'\' OR reply_to = \''. (int)$id .'\'';
		
		$db->query($query);
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('forum', array(
			'status' => new JO_Db_Expr("IF(status = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
	public static function changeReport($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'UPDATE forum_comments SET report_by = 0 WHERE id =\''. (int)$id .'\'';
		$db->query($query);
	}
	
	public static function changeSortOrder($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('forum', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$id));
	}
	
	public static function getReported() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, u.username AS owner, u1.username AS reporter 
				FROM forum_comments fc 
				JOIN users u ON u.user_id = fc.user_id
				JOIN users u1 ON u1.user_id = fc.report_by
				WHERE fc.report_by > 0 
				ORDER BY fc.datetime DESC';
				
		return $db->fetchAll($query);
	} 
}
?>