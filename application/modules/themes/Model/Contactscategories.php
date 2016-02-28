<?php

class Model_Contactscategories {

	public static function getCategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('contacts_categories')
					->order('order_index ASC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTotalCategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('contacts_categories', 'COUNT(id)');
		
		return $db->fetchOne($query);
		
	}
	
	public static function getCategory($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('contacts_categories')
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}
	
}

?>