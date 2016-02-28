<?php

class Model_Badges {

	public static function createBadge($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array(
			'name' => $data['name'],
			'visible' => $data['visible']
		);
		
		if(isset($data['sys_key'])) {
			$insert['sys_key'] = $data['sys_key'];
		}
		
		if(isset($data['type'])) {
			$insert['type'] = $data['type'];
		}
		
		if(isset($data['from'])) {
			$insert['from'] = $data['from'];
		}
		
		if(isset($data['to'])) {
			$insert['to'] = $data['to'];
		}
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		$upload_folder  = realpath(BASE_PATH . '/uploads/badges/');
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
		
		$db->insert('badges', $insert);
		return $db->lastInsertId();
	}

	public static function editeBadge($id, $data) { 
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getBadge($id);
		
		if(!$info) {
			return;
		}
		
		$update = array(
			'name' => $data['name'],
			'visible' => $data['visible']
		);
		
		if(isset($data['sys_key'])) {
			$update['sys_key'] = $data['sys_key'];
		}
		
		if(isset($data['type'])) {
			$update['type'] = $data['type'];
		}
		
		if(isset($data['from'])) {
			$update['from'] = $data['from'];
		}
		
		if(isset($data['to'])) {
			$update['to'] = $data['to'];
		}
		
		if(isset($data['deletePhoto'])) {
			$update['photo'] = '';
			if($info && $info['photo']) {
				@unlink(BASE_PATH . '/uploads/badges/' . $info['photo']);
			}
		}
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		$upload_folder  = realpath(BASE_PATH . '/uploads/badges/');
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
					@unlink(BASE_PATH . '/uploads/badges/' . $info['photo']);
				}
			}
		}
		
		$db->update('badges', $update, array('id = ?' => (int)$id));
		return $id;
	}

	public static function getBadges($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('badges')
					->order('name ASC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['filter_type']) && !is_null($data['filter_type'])) {
			$query->where('`type` = ?', $data['filter_type']);
		}
		
		return $db->fetchAll($query);
	}

	public static function getBadge($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('badges')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	public static function deleteBadge($id) {
		$info = self::getBadge($id);
		if($info && $info['photo']) {
			@unlink(BASE_PATH . '/uploads/badges/' . $info['photo']);
		}
		$db = JO_Db::getDefaultAdapter();
		$db->delete('badges', array('id = ?' => (int)$id));
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('badges', array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
}

?>