<?php

class Model_Attributes {

	public static function createAttribute($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = (JO_Request::getInstance()->getRequest('sub_of') ? 'attributes' : 'attributes_categories' );
		if($table == 'attributes') {
			$insert = array(
				'category_id' => (int)JO_Request::getInstance()->getRequest('sub_of'),
				'name' => $data['name'],
				'visible' => $data['visible'],
				'search' => $data['search'],
				'order_index' => self::getMaxOrderIndex((int)JO_Request::getInstance()->getRequest('sub_of'))
			);
		} else {
			$insert = array(
				'name' => $data['name'],
				'type' => $data['type'],
				'categories' => ($data['categories'] ? ','.implode(',', $data['categories']).',' : ''),
				'visible' => $data['visible'],
				'search' => $data['search'],
				'order_index' => self::getMaxOrderIndex((int)JO_Request::getInstance()->getRequest('sub_of'))
			);
		}
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		if($image) {
			$upload_folder  = realpath(BASE_PATH . '/uploads/attributes/');
			$upload_folder .= '/';
			
			$upload = new JO_Upload;
			$upload->setFile($image)
					->setExtension(array('.jpg','.jpeg','.png','.gif'))
					->setUploadDir($upload_folder);
					
			$new_name = md5(time() . serialize($image)); 
			if($upload->upload($new_name)) {
				$info = $upload->getFileInfo();
				if($info) {
					$insert['photo'] = $info['name'];
				}
			}
		}
		
		$db->insert($table, $insert);
		return $db->lastInsertId();
	}

	public static function editeAttribute($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = (JO_Request::getInstance()->getRequest('sub_of') ? 'attributes' : 'attributes_categories' );
		if($table == 'attributes') {
			$insert = array(
				'name' => $data['name'],
				'visible' => $data['visible'],
				'search' => $data['search']
			);
		} else {
			$insert = array(
				'name' => $data['name'],
				'type' => $data['type'],
				'categories' => ($data['categories'] ? ','.implode(',', $data['categories']).',' : ''),
				'visible' => $data['visible'],
				'search' => $data['search']
			);
		}
		
		
		$info = self::getAttribute($id);
		
		if($table == 'attributes_categories' && $info['search'] != $data['search']) {
			$qs = 'UPDATE attributes 
					SET search = \''. ($data['search'] ? 'true' : 'false') .'\' 
					WHERE category_id = \''. (int)$info['id'] .'\'';
					
			$db->query($qs);
		}
		
		if(isset($data['deletePhoto'])) {
			$insert['photo'] = '';
			if($info && $info['photo']) {
				@unlink(BASE_PATH . '/uploads/attributes/' . $info['photo']);
			}
		}
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		if($image) {
			$upload_folder  = realpath(BASE_PATH . '/uploads/attributes/');
			$upload_folder .= '/';
			
			$upload = new JO_Upload;
			$upload->setFile($image)
					->setExtension(array('.jpg','.jpeg','.png','.gif'))
					->setUploadDir($upload_folder);
					
			$new_name = md5(time() . serialize($image)); 
			if($upload->upload($new_name)) {
				$info1 = $upload->getFileInfo(); 
				if($info1) {
					$insert['photo'] = $info1['name'];
					if($info && $info['photo']) {
						@unlink(BASE_PATH . '/uploads/attributes/' . $info['photo']);
					}
				}
			}
		}
		
		$db->update($table, $insert, array('id = ?' => (int)$id));
		return $id;
	}
	
	public static function getMaxOrderIndex($sub_of) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from( ($sub_of ? 'attributes' : 'attributes_categories' ), 'MAX(order_index)')
					->where( ($sub_of ? 'category_id = ?' : '1=1' ), (int)$sub_of);
		
		return ((int)$db->fetchOne($query) + 1);
		
	}
	
	public static function getAttributes($data = array(), $where = '') {
		$db = JO_Db::getDefaultAdapter();
		
		$table = 'attributes_categories';
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$table = 'attributes';
		}
		
		$query = $db->select()
					->from($table)
					->order('order_index ASC');
	
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$query->where('category_id = ?', (int)$data['filter_sub_of']);
		}
		
		if($where) {
			$query->where($where);
		}
				
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		} 
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTotalAttributes($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = 'attributes_categories';
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$table = 'attributes';
		}
		
		$query = $db->select()
					->from($table, 'COUNT(id)');
		
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$query->where('category_id = ?', (int)$data['filter_sub_of']);
		}
		
		return $db->fetchOne($query);
		
	}
	
	public static function getAttribute($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from((JO_Request::getInstance()->getRequest('sub_of') ? 'attributes' : 'attributes_categories' ))
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}
	
	public static function getAttribute2($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('attributes_categories')
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}

	public static function changeSortOrder($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update( (JO_Request::getInstance()->getRequest('sub_of') ? 'attributes' : 'attributes_categories' ), array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$id));
	}
	
	public static function deleteAttribute($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = (JO_Request::getInstance()->getRequest('sub_of') ? 'attributes' : 'attributes_categories' );
		
		if($table == 'attributes_categories') {
			$childrens = self::getAttributes(array(
				'filter_sub_of' => $id
			));
			if($childrens) {
				foreach($childrens AS $child) {
					$db->delete('attributes', array('id = ?' => (int)$child['id']));
					$db->delete('items_attributes', array('attribute_id = ?' => (int)$child['id']));
					if($child['photo']) {
						@unlink(BASE_PATH . '/uploads/attributes/' . $child['photo']);
					}
				}
			}
		}
		
		$info = self::getAttribute($id);
		if($info && isset($info['photo']) && $info['photo']) {
			@unlink(BASE_PATH . '/uploads/attributes/' . $info['photo']);
		}
		$db->delete($table, array('id = ?' => (int)$id));
		if($table == 'attributes_categories') {
			$db->delete('items_attributes', array('category_id = ?' => (int)$id));
		} else {
			$db->delete('items_attributes', array('attribute_id = ?' => (int)$id));
		}
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update((JO_Request::getInstance()->getRequest('sub_of') ? 'attributes' : 'attributes_categories' ), array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
	public static function changeRequired($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('attributes_categories', array(
			'required' => new JO_Db_Expr("IF(required = 1, 0, 1)")
		), array('id = ?' => (int)$id));
	}
	
	public function getAllWithCategories($where='') {
    
    	$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('attributes_categories', new JO_Db_Expr('attributes_categories.id AS head_id, attributes_categories.name AS head_name, attributes_categories.required, attributes_categories.type, attributes.*'))
					->joinLeft('attributes', 'attributes.category_id = attributes_categories.id AND attributes.visible = \'true\'')
					->where('attributes_categories.visible = ?', 'true')
					->order('attributes_categories.order_index ASC')
					->order('attributes.order_index ASC');
		
		if($where) {
			$query->where($where);
		}
		//echo $query;
		return $db->fetchAll($query);
	}
}

?>