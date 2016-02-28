<?php

class Model_Bulletinemails {
	
	public static function getEmails($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('bulletin_emails')
					->order('id DESC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		//filters
		
		if(isset($data['filter_bulletin_subscribe'])) {
			$query->where('bulletin_subscribe = ?', $data['filter_bulletin_subscribe']);
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('(firstname LIKE ? OR lastname LIKE ?)', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_email']) && $data['filter_email']) {
			$query->where('email LIKE ?', '%' . $data['filter_email'] . '%');
		}
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTotalEmails($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('bulletin_emails', 'COUNT(id)');
		
		//filters
		
		if(isset($data['filter_bulletin_subscribe'])) {
			$query->where('bulletin_subscribe = ?', $data['filter_bulletin_subscribe']);
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('(firstname LIKE ? OR lastname LIKE ?)', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_email']) && $data['filter_email']) {
			$query->where('email LIKE ?', '%' . $data['filter_email'] . '%');
		}
		
		return $db->fetchOne($query);
		
	}
	
	public static function getEmail($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('bulletin_emails')
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}
	
	public static function deleteEmail($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('bulletin_emails', array('id = ?' => (int)$id));
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('bulletin_emails', array(
			'bulletin_subscribe' => new JO_Db_Expr("IF(bulletin_subscribe = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}

}

?>