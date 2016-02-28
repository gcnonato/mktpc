<?php

class Model_Socials {
	
	public static function getSocials() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
			->from('socials')
			->order('order_index');
			
		return $db->fetchAll($query);
	}
	
	public static function getSocial($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
			->from('socials')
			->where('id = ?', (int)$id);
			
		return $db->fetchRow($query);
	}
	
	public static function createSocials($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array(
			'name' => $data['name'],
			'link' => $data['link'],
			'visible' => $data['visible'],
			'order_index' => self::getMaxOrderIndex()
		);
		
		$image = JO_Request::getInstance()->getFile('photo');
		if($image) {
			$upload_folder = realpath(BASE_PATH . '/uploads/socials/') . '/';
			
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
		
		$db->insert('socials', $insert);
		
		return $db->lastInsertId();
	}
	
	public function getMaxOrderIndex() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('socials', 'MAX(order_index)');
		
		return ((int)$db->fetchOne($query) + 1);
	}
	
	public static function editeSocials($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$upload_folder = realpath(BASE_PATH . '/uploads/socials/') . '/';
		
		$info = self::getSocial($data['id']);
		
		$updates = array(
			'name' => $data['name'],
			'link' => $data['link'],
			'visible' => $data['visible']
		);
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		if(!empty($image['tmp_name'])) {
			
			if($info && file_exists($upload_folder . $info['photo'])) {
				@unlink($upload_folder . $info['photo']);
			}
			
			$upload = new JO_Upload;
			$upload->setFile($image)
					->setExtension(array('.jpg','.jpeg','.png','.gif'))
					->setUploadDir($upload_folder);
					
			$new_name = md5(time() . serialize($image)); 
			if($upload->upload($new_name)) {
				$info = $upload->getFileInfo();
				if($info) {
					$updates['photo'] = $info['name'];
				}
			}
		} elseif($data['deletePhoto']) {
			
			if(file_exists($upload_folder . $info['photo'])) {
				@unlink($upload_folder . $info['photo']);
			}
			
			$updates['photo'] = '';
		}
		
		$db->update('socials', $updates);
	}
	
	public static function deleteSocials($id) {
		$db = JO_Db::getDefaultAdapter();
		$upload_folder = realpath(BASE_PATH . '/uploads/socials/') . '/';
		
		$info = self::getSocial($id);
		
		if($info) {
			
			if(file_exists($upload_folder . $info['photo']))
				@unlink($upload_folder . $info['photo']);
			
			$db->delete('socials',  array('id = ?' => (int)$id));
		}
	}
	
	public function changeSortOrder($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('socials', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$id));
	}
	
	public function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('socials', array(
			'visible' => new JO_Db_Expr("IF(visible = 1, 0, 1)")
		), array('id = ?' => (int)$id));
	}
}

?>