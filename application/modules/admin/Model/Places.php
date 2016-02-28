<?php

class Model_Places {
	
	public function createPlace($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$parent_id = (isset($data['parent_id']) ? $data['parent_id'] : '0');
		
		if($data['level'] == 1 && (!$data['Lat'] || !$data['Lng'])) {
			if(isset($data['description'][JO_Registry::get('config_language_id')]['title'])) {
				$place_title = $data['description'][JO_Registry::get('config_language_id')]['title'];

				$get_cordinates = Model_GoogleApi::getCordinatesByPlace($place_title);
				$data['Lat'] = $get_cordinates['Lat'];
				$data['Lng'] = $get_cordinates['Lng'];
			}
		}
		
		$db->insert('places', array(
			'image' => $data['image'],
			'parent_id' => $parent_id,
			'date_added' => new JO_Db_Expr('NOW()'),
			'status' => $data['status'],
			'Lat' => (float)(isset($data['Lat']) ? $data['Lat'] : '0'),
			'Lng' => (float)(isset($data['Lng']) ? $data['Lng'] : '0'),
			'level' => $data['level']
		));
		
		$place_id = $db->lastInsertId();
		
		if(isset($data['description'])) {
			foreach($data['description'] AS $language_id => $value) {
				$db->insert('places_description', array(
					'place_id' => $place_id,
					'language_id' => $language_id,
					'meta_title' => $value['meta_title'],
					'meta_description' => $value['meta_description'],
					'meta_keywords' => $value['meta_keywords'],
					'title' => $value['title'],
					'description' => $value['description']
				));
			}
		}
		
		if(isset($data['type'])) {
			foreach($data['type'] AS $type) {
				$db->insert('places_types', array(
					'place_id' => (int)$place_id,
					'option_item_id' => (int)$type
				));
			}
		}
		
		if(isset($data['keyword']) && trim($data['keyword'])) {
			Model_AutoSeo::generatePlace($place_id, $data['keyword']);
		} else {
			Model_AutoSeo::generatePlace($place_id);
		}
		
		
		return $place_id;
	}
	
	public function editePlace($place_id, $data) { 
		$db = JO_Db::getDefaultAdapter();
		
		$parent_id = (isset($data['parent_id']) ? $data['parent_id'] : '0');
		
		if($data['level'] == 1 && ($data['Lat'] == 0.00000000 || $data['Lng'] == 0.00000000)) {
			if(isset($data['description'][JO_Registry::get('config_language_id')]['title'])) {
				$place_title = $data['description'][JO_Registry::get('config_language_id')]['title'];

				$get_cordinates = Model_GoogleApi::getCordinatesByPlace($place_title);
				$data['Lat'] = $get_cordinates['Lat'];
				$data['Lng'] = $get_cordinates['Lng'];
			}
		}
		
		$db->update('places', array(
			'image' => $data['image'],
			'parent_id' => $parent_id,
			'date_modified' => new JO_Db_Expr('NOW()'),
			'status' => $data['status'],
			'Lat' => (float)(isset($data['Lat']) ? $data['Lat'] : '0'),
			'Lng' => (float)(isset($data['Lng']) ? $data['Lng'] : '0'),
			'level' => $data['level']
		), array('place_id = ?' => (int)$place_id));
		
		$db->delete('places_description', array('place_id = ?' => (int)$place_id));
		if(isset($data['description'])) {
			foreach($data['description'] AS $language_id => $value) {
				$db->insert('places_description', array(
					'place_id' => $place_id,
					'language_id' => $language_id,
					'meta_title' => $value['meta_title'],
					'meta_description' => $value['meta_description'],
					'meta_keywords' => $value['meta_keywords'],
					'title' => $value['title'],
					'description' => $value['description']
				));
			}
		}
		
		$db->delete('places_types', array('place_id = ?' => (int)$place_id));
		if(isset($data['type'])) {
			foreach($data['type'] AS $type) {
				$db->insert('places_types', array(
					'place_id' => (int)$place_id,
					'option_item_id' => (int)$type
				));
			}
		}
		
		if(isset($data['keyword']) && trim($data['keyword'])) {
			Model_AutoSeo::generatePlace($place_id, $data['keyword']);
		} else {
			Model_AutoSeo::generatePlace($place_id);
		}
		
		self::fixAllRecordsTypeUrlQuery();
		
		return $place_id;
	}
	
	public function changeStatus($place_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('places', array(
			'status' => new JO_Db_Expr('IF(status = 1, 0, 1)')
		), array('place_id = ?' => (int)$place_id));
	}
	
	private function getRecordTypeSum($parent_id) {
		$db = JO_Db::getDefaultAdapter();
		$sub_query = $db->select()
						->from('places', array(new JO_Db_Expr("COUNT(place_id)")))
						->where('parent_id = ?', (int)$parent_id);
		return $db->fetchOne($sub_query);
	}
	
	private function fixHelp($parent_id, $url1) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('places', array('*', new JO_Db_Expr("(SELECT keyword FROM url_alias WHERE query=CONCAT('place_id=',places.place_id) LIMIT 1) AS keyword")))
					->where('parent_id = ?', (int)$parent_id);
		$results = $db->fetchAll($query);
		if($results) {
			foreach($results AS $result) {
				$url = $url1 . '/' . $result['keyword']; 
				$db->update('places',array(
					'keyword' => trim($url, '/')
				), array('place_id = ?' => (int)$result['place_id']));
				if(self::getRecordTypeSum($result['place_id'])) {
					self::fixHelp($result['place_id'], $url);
				}
			}
		}
	}
	
	public function fixAllRecordsTypeUrlQuery() {
		set_time_limit(0);
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('places', array('*', new JO_Db_Expr("(SELECT keyword FROM url_alias WHERE query=CONCAT('place_id=',places.place_id) LIMIT 1) AS keyword")))
					->where('parent_id = ?', 0);
		$results = $db->fetchAll($query);
		if($results) {
			foreach($results AS $result) {
				self::fixHelp($result['place_id'], ('/' . $result['keyword']));
				$db->update('places',array(
					'keyword' => trim($result['keyword'], '/')
				), array('place_id = ?' => (int)$result['place_id']));
			}
		} 
	}
	
	
	public function changeSortOrder($place_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('places', array(
			'sort_order' => $sort_order
		), array('place_id = ?' => (int)$place_id));
	}
	
	public function changeSortOrderByName($parent_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('places', array('place_id'))
					->joinLeft('places_description', "places.place_id = places_description.place_id", array())
					->where('language_id = ?', JO_Registry::get('config_language_id'))
					->order('places_description.title ASC');
		$results = $db->fetchAll($query);
		if($results) {
			foreach($results AS $key => $result) {
				self::changeSortOrder($result['place_id'], ($key+1));
			}
		}
	}
	
	public function deletePlace($place_id) {
		$db = JO_Db::getDefaultAdapter();
		
		if($place_id) {
			$child = $db->select()
						->from('places')
						->where('parent_id = ?', (int)$place_id);

			$results_child = $db->fetchAll($child);
			if($results_child) {
				foreach($results_child AS $result_child) {
					self::deletePlace($result_child['place_id']);
				}
			}
		}
		
		$db->delete('places', array('place_id = ?' => (int)$place_id));
		$db->delete('places_description', array('place_id = ?' => (int)$place_id));
		$db->query("DELETE FROM url_alias WHERE query = 'place_id=" . (int)$place_id . "'");
	}
	
	public static function getPlaceType($place_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('places_types', array('option_item_id', 'option_item_id'))
					->where('place_id = ?', (int)$place_id);
		return $db->fetchPairs($query);
	}
	
	public function getPlaces($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('places')
					->joinLeft('places_description', "places.place_id = places_description.place_id", array('title'))
					->where('language_id = ?', JO_Registry::get('config_language_id'))
					->order('places.sort_order ASC');
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['parent_id'])) {
			$query->where('places.parent_id = ?', (int)$data['parent_id']);
		}
		
		if(isset($data['parent_id_is_not'])) {
			$query->where('places.parent_id != ?', (int)$data['parent_id_is_not']);
		}
		
		if(isset($data['filter_place_title']) && !is_null($data['filter_place_title'])) {
			$query->where('places_description.title LIKE ?', '%' . (string)$data['filter_place_title'] . '%');
		}
		
		return $db->fetchAll($query);
		
	}
	
	public function getPlace($place_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('places', array('*', new JO_Db_Expr("(SELECT keyword FROM url_alias WHERE query='place_id=".(int)$place_id."' LIMIT 1) AS keyword")))
					->joinLeft('places_description', "places.place_id = places_description.place_id")
					->where('language_id = ?', JO_Registry::get('config_language_id'))
					->where('places.place_id = ? ', (int)$place_id);
		
		return $db->fetchRow($query);
		
	}
	
	public function getPlaceDescription($place_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('places_description')
					->where('place_id = ?', (int)$place_id);
		
		$data = array();
		$resulsts = $db->fetchAll($query);
		if($resulsts) {
			foreach($resulsts AS $resulst) {
				$data[$resulst['language_id']] = $resulst;
			}
		}			
		return $data;
	}
	
	

}

?>