<?php

class Model_Contactscategories {

	public static function createCategory($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('contacts_categories', array(
			'name' => $data['name'],
			'text' => $data['text'],
			'visible' => $data['visible'],
			'order_index' => self::getMaxOrderIndex()
		));
		return $db->lastInsertId();
	}

	public static function editeCategory($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('contacts_categories', array(
			'name' => $data['name'],
			'text' => $data['text'],
			'visible' => $data['visible']
		), array('id = ?' => (int)$id));
		return $id;
	}
	
	public static function getMaxOrderIndex($sub_of) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('contacts_categories', 'MAX(order_index)');
		
		return ((int)$db->fetchOne($query) + 1);
		
	}
	
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

	public static function changeSortOrder($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('contacts_categories', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$id));
	}
	
	public static function deleteCategory($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('contacts_categories', array('id = ?' => (int)$id));
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('contacts_categories', array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
}

?>