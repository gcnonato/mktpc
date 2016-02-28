<?php

class Model_Comments {

	public static function getComments($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_comments')
					->join(Model_Users::getPrefixDB().'users', 'items_comments.user_id = users.user_id', 'username')
					->order('items_comments.id DESC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['filter_item_id']) && $data['filter_item_id']) {
			$query->where('item_id = ?', (int)$data['filter_item_id']);
		}
	
		return $db->fetchAll($query);
	}

	public static function getTotalComments($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_comments', 'COUNT(id)')
					->join(Model_Users::getPrefixDB().'users', 'items_comments.user_id = users.user_id', array());
		
		if(isset($data['filter_item_id']) && $data['filter_item_id']) {
			$query->where('item_id = ?', (int)$data['filter_item_id']);
		}
		
		return $db->fetchOne($query);
	}

	public static function getReportedComments($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_comments')
					->join(Model_Users::getPrefixDB().'users', 'items_comments.report_by = users.user_id', 'username')
					->order('items_comments.id DESC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
	
		return $db->fetchAll($query);
	}

	public static function getTotalReportedComments($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_comments', 'COUNT(id)')
					->join(Model_Users::getPrefixDB().'users', 'items_comments.report_by = users.user_id', array());
		
		return $db->fetchOne($query);
	}

	public static function getComment($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_comments')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	public static function setPreviewed($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('items_comments', array(
			'report_by' => 0
		), array('id = ?' => (int)$id));
	}
	
	public static function deleteComment($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getComment($id);
		if(!$info) {
			return;
		}
		
		if($info['reply_to'] == '0') {
			$db->update('items', array(
				'comments' => new JO_Db_Expr('comments - 1')
			), array('id = ?' => (int)$info['item_id']));
		}
		
		$db->delete('items_comments', array('id = ?' => (int)$id));
		$db->delete('items_comments', array('reply_to = ?' => (int)$id));
	}
	
}

?>