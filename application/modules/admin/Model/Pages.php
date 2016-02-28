<?php

class Model_Pages {
	
	public static function getMaxOrderIndex($sub_of) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pages', 'MAX(order_index)')
					->where('sub_of = ?', (int)$sub_of);
		
		return ((int)$db->fetchOne($query) + 1);
		
	}
	
	public static function createPage($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('pages', array(
			'sub_of' => (isset($data['sub_of']) ? $data['sub_of'] : 0),
			'key' => ($data['key'] ? $data['key'] : $data['name']),
			'meta_title' => $data['meta_title'],
			'meta_keywords' => $data['meta_keywords'],
			'meta_description' => $data['meta_description'],
		    'url'  => $data['url'],
			'menu' => $data['menu'],
			'footer' => $data['footer'],
			'visible' => $data['visible'],
			'order_index' => self::getMaxOrderIndex((isset($data['sub_of']) ? $data['sub_of'] : 0))
		));
		
		$last_id = $db->lastInsertId();
		
		/*$query = 'INSERT INTO pages_description (`id`, `lid`, `name`, `text`) VALUES (';
		
		$description = array();
		foreach($data['text'] as $k => $v) {
			$description[] = $last_id .', \''. (int)$k .'\', \''. $data['name'][$k] .'\',\''. $v .'\'';
		}
		
		$query .= implode('), (', $description) .')';
		$db->query($query);*/
		
		foreach($data['text'] as $k => $v) {
			//$description[] = (int)$page_id .', \''. (int)$k .'\', \''. $data['name'][$k] .'\',\''. $v .'\'';
			$db->insert('pages_description', array(
				'id' => (int)$last_id,
				'lid' => (int)$k,
				'name' => $data['name'][$k],
				'text' => $v
			));
		}
		
	}
	
	public static function editePage($page_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('pages', array(
			'key' => ($data['key'] ? $data['key'] : $data['name']),
			'meta_title' => $data['meta_title'],
			'meta_keywords' => $data['meta_keywords'],
			'meta_description' => $data['meta_description'],
		    'url'  => $data['url'],
			'menu' => $data['menu'],
			'footer' => $data['footer'],
			'visible' => $data['visible']
		), array('id = ?' => (int)$page_id));
		
		$db->delete('pages_description', array(
			'id = ?' => (int) $page_id
		));
		
		//$query = 'INSERT INTO pages_description (`id`, `lid`, `name`, `text`) VALUES (';
		
		//$description = array();
		foreach($data['text'] as $k => $v) {
			//$description[] = (int)$page_id .', \''. (int)$k .'\', \''. $data['name'][$k] .'\',\''. $v .'\'';
			$db->insert('pages_description', array(
				'id' => (int)$page_id,
				'lid' => (int)$k,
				'name' => $data['name'][$k],
				'text' => $v
			));
		}
		
		//$query .= implode('), (', $description) .')';
		
		//$db->query($query);
		
		return $page_id;
	}
	
	public static function getPages($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Registry::get('default_config_language_id') .'\'', array('lid','name','text'))
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

		return $db->fetchAll($query);
	}
	
	public static function getTotalPages($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages', new JO_Db_Expr('COUNT(id)'));
		
		if(isset($data['filter_sub_of']) && !is_null($data['filter_sub_of'])) {
			$query->where('sub_of = ?', (int)$data['filter_sub_of']);
		}

		return $db->fetchOne($query);
	}
	
	public static function getPage($page_id) {
		$db = JO_Db::getDefaultAdapter();

		$query = 'SELECT p.*, pd.lid, pd.name, pd.text FROM pages p
				LEFT JOIN pages_description pd ON pd.id = p.id
				WHERE p.id = \''. (int)$page_id .'\'';
				
		return $db->fetchAll($query);
	}
	
	
	public static function changeStatus($page_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pages', array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$page_id));
	}
	
	public static function deletePage($id) {
		$db = JO_Db::getDefaultAdapter();
		$childrens = self::getPages(array(
			'filter_sub_of' => $id
		));
		if($childrens) {
			foreach($childrens AS $child) {
				self::deletePage($child['id']);
			}
		}
		$db->delete('pages', array('id = ?' => (int)$id));
		$db->delete('pages_description', array('id = ?' => (int)$id));
	}
	
	public static function changeSortOrder($page_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pages', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$page_id));
	}
	
	public static function getPagePath($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Registry::get('default_config_language_id') .'\'', array('lid','name','text'))
					->where('pages.id = ?', (int)$id);
		$result = $db->fetchRow($query);
		
		if(!$result) {
			return;
		}
		$name = $result['name'];
		if($result && $result['sub_of']) {
			$res = self::getPagePath($result['sub_of']);
			$name = $res['name'] . ' › ' . $name;
		}
		
		return array(
			'name' => $name,
			'sub_of' => $result['sub_of']
		);
	}

	public static function getPagesFromParent($parent_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Registry::get('default_config_language_id') .'\'', array('lid','name','text'))
					->order('order_index ASC')
					->where('sub_of = ?', (int)$parent_id);
		
		$result = array();
				
		$data_info = $db->fetchAll($query);
		if($data_info) {
			foreach($data_info AS $info) {
				$children = self::getPagesFromParent($info['id']);
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
		$result = self::getPage($record_type_id);
		
		if($result && $result[0]['sub_of']) {
			return self::getPath($result[0]['sub_of']) . ' >> ' . $result[0]['name'];
		} else {
			foreach($result as $r) {
				if($r['lid'] == JO_Registry::get('default_config_language_id'))	
				return $r['name'];
			}
		}
	}
	
}

?>