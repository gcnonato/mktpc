<?php

class Model_Categories {

	public static function createCategory($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('categories', array(
			'sub_of' => (isset($data['sub_of']) ? $data['sub_of'] : 0),
			'meta_title' => $data['meta_title'],
			'meta_keywords' => $data['meta_keywords'],
			'meta_description' => $data['meta_description'],
			'visible' => $data['visible'],
			'module' => $data['default_module'],
			'order_index' => self::getMaxOrderIndex((isset($data['sub_of']) ? $data['sub_of'] : 0))
		));
		
		$last_id = $db->lastInsertId();
		
		$query = 'INSERT INTO categories_description (`id`, `lid`, `name`) VALUES (';
		
		$description = array();
		foreach($data['name'] as $k => $v) {
			$description[] = $last_id .', \''. (int)$k .'\', \''. $v .'\'';
		}
		
		$query .= implode('), (', $description) .')';
		
		$db->query($query);
	}

	public static function editeCategory($id, $data) {
		
		$info = self::getCategory($id);
		if(!$info) {
			return;
		}
		
		$db = JO_Db::getDefaultAdapter();
		$db->update('categories', array(
			'meta_title' => $data['meta_title'],
			'meta_keywords' => $data['meta_keywords'],
			'meta_description' => $data['meta_description'],
			'visible' => $data['visible'],
			'module' => $data['default_module']
		), array('id = ?' => (int)$id));
		
		$db->delete('categories_description', array(
			'id = ?' => (int) $id
		));
		
		$query = 'INSERT INTO categories_description (`id`, `lid`, `name`) VALUES (';
		
		$description = array();
		foreach($data['name'] as $k => $v) {
			$description[] = (int)$id .', \''. (int)$k .'\', \''. $v .'\'';
		}
		
		$query .= implode('), (', $description) .')';
		
		$db->query($query);
		
//		if($info['module'] != $data['module']) {
		self::updateChildrenModule($id, $data['default_module']);
//		}
		
		return $id;
	}
	
	public static function updateChildrenModule($id, $module) {
		$list = self::getCategories(array(
			'filter_sub_of' => $id
		));
		if($list) {
			foreach($list AS $v) {
				self::updateChildrenModule($v['id'], $module);
			}
		}
		$db = JO_Db::getDefaultAdapter();
		$db->update('categories', array('module' => $module), array('sub_of = ?' => (int)$id));
	}
	
	public static function getMaxOrderIndex($sub_of) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('categories', 'MAX(order_index)')
					->where('sub_of = ?', (int)$sub_of);
		
		return ((int)$db->fetchOne($query) + 1);
		
	}
	
	public static function getCategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Registry::get('default_config_language_id') .'\'', array('lid','name'))
					->order('order_index ASC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['filter_sub_of']) && !is_null($data['filter_sub_of'])) {
			$query->where('sub_of = ?', (int)$data['filter_sub_of']);
		}
		
		if(isset($data['filter_module']) && !is_null($data['filter_module'])) {
			$query->where('module = ?', $data['filter_module']);
		}
		
		if(isset($data['filter_id_key']) && $data['filter_id_key'] === true) {
			$data = array();
			$results = $db->fetchAll($query);
			if($results) {
				foreach($results AS $result) {
					$data[$result['id']] = $result;
				}
			}
			return $data;
		} elseif(isset($data['filter_concat']) && $data['filter_concat'] === true) { 
			$data = array();
			$results = $db->fetchAll($query);
			if($results) {
				foreach($results AS $result) {
					$data[$result['id']] = $result['id'];
				}
			}
			return $data;
		} else {
			return $db->fetchAll($query);
		}
		
	}
	
	public static function getCategoriesFromParent($parent_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Registry::get('default_config_language_id') .'\'', array('lid','name'))
					->order('order_index ASC')
					->where('sub_of = ?', (int)$parent_id);
		
		$result = array();
					
		$data_info = $db->fetchAll($query);
		if($data_info) {
			foreach($data_info AS $info) {
				$children = self::getCategoriesFromParent($info['id']);
				$result[] = array(
					'sub_of' => $info['sub_of'],
					'id' => $info['id'],
					'order_index' => $info['order_index'],
					'visible' => $info['visible'],
					'name' => self::getPath($info['id']),
					'children' => ($children ? true : false)
				);
				
				$result = array_merge($result, $children);
			}
		}
		
		return $result;
		
	}
	
	public static function getCategoriesFromParentByModule($parent_id, $module) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Registry::get('default_config_language_id') .'\'', array('lid','name'))
					->order('order_index ASC')
					->where('sub_of = ?', (int)$parent_id)
					->where('module = ?', $module);
		
		$result = array();
					
		$data_info = $db->fetchAll($query);
		if($data_info) {
			foreach($data_info AS $info) {
				$children = self::getCategoriesFromParent($info['id']);
				$result[] = array(
					'sub_of' => $info['sub_of'],
					'id' => $info['id'],
					'order_index' => $info['order_index'],
					'visible' => $info['visible'],
					'name' => self::getPath($info['id']),
					'children' => ($children ? true : false)
				);
				
				$result = array_merge($result, $children);
			}
		}
		
		return $result;
		
	}
	
	public function getPath($record_type_id) {
		$result = self::getCategory($record_type_id);
		
		if($result && $result[0]['sub_of']) {
			return self::getPath($result[0]['sub_of']) . ' >> ' . $result[0]['name'];
		} else {
			foreach($result as $r) {
				if($r['lid'] == JO_Registry::get('default_config_language_id'))	
				return $r['name'];
			}
		}
	}
	
	public static function getTotalCategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('categories', 'COUNT(id)');
		
		if(isset($data['filter_sub_of']) && !is_null($data['filter_sub_of'])) {
			$query->where('sub_of = ?', (int)$data['filter_sub_of']);
		}
		
		return $db->fetchOne($query);
		
	}
	
	public static function getCategory($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id', array('lid','name'))
					->where('categories.id = ?', (int)$id);
		return $db->fetchAll($query);
	}
	
	public static function getCategoryPath($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('categories')
					->where('id = ?', (int)$id);
		$result = $db->fetchRow($query);
		if(!$result) {
			return;
		}
		$name = $result['name'];
		$module = $result['module'];
		if($result && $result['sub_of']) {
			$res = self::getCategoryPath($result['sub_of']);
			$name = $res['name'] . ' › ' . $name;
			$module = $res['module'];
		}
		
		return array(
			'name' => $name,
			'sub_of' => $result['sub_of'],
			'module' => $module
		);
	}
	
	
//	public static function sortOrder($id, $sub_of = 0, $type = 'DESC') {
//		$db = JO_Db::getDefaultAdapter();
//		$query = $db->select()
//					->from('categories')
//					->where('id = ?', (int)$id);
//		$row = $db->fetchRow($query);
//		
//		if($row) {
//			$query = $db->select()
//					->from('categories', ($type == 'DESC' ? 'MAX(order_index)' : 'MIN(order_index)'))
//					->where('sub_of = ?', (int)$sub_of);
//			$minMax = $db->fetchOne($query);
//			
//			if($type == 'DESC' && $row['order_index'] < $minMax) { 
//				$sel = $db->select()
//							->from('categories')
//							->where('order_index > ?', (int)$row['order_index'])
//							->where('sub_of = ?', (int)$sub_of)
//							->order('order_index ASC')
//							->limit(1);
//				$changeRow = $db->fetchRow($sel);
//				if(!$changeRow) {
//					return false;
//				}
//				
//				$db->update('categories', array('order_index' => $row['order_index']), array('id = ?' => $changeRow['id']));
//				$db->update('categories', array('order_index' => $changeRow['order_index']), array('id = ?' => $row['id']));
//				
//				return true;
//			} elseif($type == 'ASC' && $row['order_index'] > $minMax) { 
//				$sel = $db->select()
//							->from('categories')
//							->where('order_index < ?', (int)$row['order_index'])
//							->where('sub_of = ?', (int)$sub_of)
//							->order('order_index DESC')
//							->limit(1);
//				$changeRow = $db->fetchRow($sel);
//				if(!$changeRow) {
//					return false;
//				}
//				
//				$db->update('categories', array('order_index' => $row['order_index']), array('id = ?' => $changeRow['id']));
//				$db->update('categories', array('order_index' => $changeRow['order_index']), array('id = ?' => $row['id']));
//				
//				return true;
//			}
//			
//		}
//	}

	public static function changeSortOrder($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('categories', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$id));
	}
	
	public static function deleteCategory($id) {
		$db = JO_Db::getDefaultAdapter();
		$childrens = self::getCategories(array(
			'filter_sub_of' => $id
		));
		if($childrens) {
			foreach($childrens AS $child) {
				self::deleteCategory($child['id']);
			}
		}
		$db->delete('categories', array('id = ?' => (int)$id));
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('categories', array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
	/////////////// help

	public static function getCategoryParents($categories, $categoryID) {
		
		$return = ''; 
		if(isset($categories[$categoryID])) {
			$return .= $categoryID.',';
			$return .= self::getCategoryParents($categories, $categories[$categoryID]['sub_of']);
		}
		
		return $return;
		
	}
	
	public static function getParentCategoryByItem($item_id, $temp = false) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT categories 
				FROM '. ($temp ? 'temp_' : '') .'items_to_category 
				WHERE item_id = \''. (int)$item_id .'\'
					AND (LENGTH(categories) - LENGTH(REPLACE(categories, \',\', \'\'))) = 2';
					
		return $db->fetchRow($query);
	}
	
}

?>