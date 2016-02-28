<?php

class Model_Countries {

	public static function createCountry($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array(
			'name' => $data['name'],
			'visible' => $data['visible']
		);
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		$upload_folder  = realpath(BASE_PATH . '/uploads/countries/');
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
		
		$db->insert('countries', $insert);
		return $db->lastInsertId();
	}

	public static function editeCountry($id, $data) { 
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getCountry($id);
		
		if(!$info) {
			return;
		}
		
		$update = array(
			'name' => $data['name'],
			'visible' => $data['visible']
		);
		
		if(isset($data['deletePhoto'])) {
			$update['photo'] = '';
			if($info && $info['photo']) {
				@unlink(BASE_PATH . '/uploads/countries/' . $info['photo']);
			}
		}
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		$upload_folder  = realpath(BASE_PATH . '/uploads/countries/');
		$upload_folder .= '/';
		
		
		$upload = new JO_Upload;
		$upload->setFile($image)
				->setExtension(array('.jpg','.jpeg','.png','.gif'))
				->setUploadDir($upload_folder);
				
		$new_name = md5(time() . serialize($image)); 
		if($upload->upload($new_name)) {
			$info1 = $upload->getFileInfo();
			if($info1) {
				$update['photo'] = $info1['name'];
				if($info && $info['photo']) {
					@unlink(BASE_PATH . '/uploads/countries/' . $info['photo']);
				}
			}
		}
		
		$db->update('countries', $update, array('id = ?' => (int)$id));
		return $id;
	}

	public static function getCountries($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('countries')
					->order('name ASC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalCountries() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('countries', 'COUNT(id)');
		
		return $db->fetchOne($query);
	}

	public static function getCountry($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('countries')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	public static function deleteCountry($id) {
		$info = self::getCountry($id);
		if($info && $info['photo']) {
			@unlink(BASE_PATH . '/uploads/countries/' . $info['photo']);
		}
		$db = JO_Db::getDefaultAdapter();
		$db->delete('countries', array('id = ?' => (int)$id));
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('countries', array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
}

?>