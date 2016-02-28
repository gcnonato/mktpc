<?php

class Model_Tags {

	public static function createTag($data) {
		
		$tid = self::getTagByTitleAndInsert(trim($data['name']));
		if($tid) {
			return -1;
		}
		
		$db = JO_Db::getDefaultAdapter();
		$db->insert('tags', array(
			'name' => $data['name'],
			'visible' => $data['visible']
		));
		return $db->lastInsertId();
	}

	public static function editeTag($id, $data) {
		
		$tid = self::getTagByTitleAndInsert(trim($data['name']));
		if($tid && $tid != $id) {
			return -1;
		}
		
		$db = JO_Db::getDefaultAdapter();
		$db->update('tags', array(
			'name' => $data['name'],
			'visible' => $data['visible']
		), array('id = ?' => (int)$id));
		return $id;
	}

	public static function getTags($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('tags')
					->order('name ASC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
	    if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('id = ?', (int)$data['filter_id']);
		}
		
		
		if(isset($data['filter_visible']) && $data['filter_visible']) {
			$query->where('visible = ?',  $data['filter_visible']);
		}
		return $db->fetchAll($query);
	}
	
	public static function getTotalTags($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('tags', 'COUNT(id)');
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		return $db->fetchOne($query);
	}

	public static function getTag($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('tags')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	public static function deleteTag($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('tags', array('id = ?' => (int)$id));
		$db->delete('items_tags', array('tag_id = ?' => (int)$id));
		$db->delete('temp_items_tags', array('tag_id = ?' => (int)$id));
	}
	
	public static function changeStatus($page_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('tags', array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$page_id));
	}
	
	public static function getTagByTitleAndInsert($tag) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('tags')
					->where('name LIKE ?', $tag);
		
		$result = $db->fetchRow($query);
		if($result) {
			return $result['id'];
		} else {
			$db->insert('tags', array(
				'name' => $tag,
				'visible' => 'true'
			));
			return $db->lastInsertId();
		}
		return false;
	}
	
}

?>