<?php

class Model_Collections {
	
	public static function getCollections($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('collections')
					->joinLeft(Model_Users::getPrefixDB().'users', 'collections.user_id = users.user_id', 'username')
					->order('id DESC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalCollections($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('collections', 'COUNT(id)');
		
		return $db->fetchOne($query);
	}
	
	public static function getCollection($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('collections')
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}
	
	public static function deleteCollection($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('collections', array('id = ?' => (int)$id));
		$db->delete('items_collections', array('collection_id = ?' => (int)$id));
		$db->delete('collections_rates', array('collection_id = ?' => (int)$id));
	}

}

?>