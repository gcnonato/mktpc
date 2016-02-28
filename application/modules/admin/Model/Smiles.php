<?php

class Model_Smiles {

	public static function createSmile($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array(
			'name' => $data['name'],
			'visible' => $data['visible'],
			'code' => $data['code']
		);
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		if(!file_exists(BASE_PATH . '/uploads/smiles/')) {
			mkdir(BASE_PATH . '/uploads/smiles/', 0777, true);
		}
		
		$upload_folder  = realpath(BASE_PATH . '/uploads/smiles/');
		$upload_folder .= '/';
		
		
		$upload = new JO_Upload;
		$upload->setFile($image)
				->setExtension(array('.jpg','.jpeg','.png','.gif'))
				->setUploadDir($upload_folder);
				
		$new_name = md5(time() . serialize($image)); 
		if($upload->upload($new_name)) {
			$info = $upload->getFileInfo();
			if($info) {
				$insert['photo'] = '/smiles/'.$info['name'];
			}
		}
		
		$db->insert('smiles', $insert);
		return $db->lastInsertId();
	}

	public static function editeSmile($id, $data) { 
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getSmile($id);
		
		if(!$info) {
			return;
		}
		
		$update = array(
			'name' => $data['name'],
			'visible' => $data['visible'],
			'code' => $data['code']
		);
		
		if(isset($data['deletePhoto'])) {
			$update['photo'] = '';
			if($info && $info['photo']) {
				@unlink(BASE_PATH . '/uploads/' . $info['photo']);
			}
		}
		
		$image = JO_Request::getInstance()->getFile('photo');
		
		if(!file_exists(BASE_PATH . '/uploads/smiles/')) {
			mkdir(BASE_PATH . '/uploads/smiles/', 0777, true);
		}
		
		$upload_folder  = realpath(BASE_PATH . '/uploads/smiles/');
		$upload_folder .= '/';
		
		
		$upload = new JO_Upload;
		$upload->setFile($image)
				->setExtension(array('.jpg','.jpeg','.png','.gif'))
				->setUploadDir($upload_folder);
				
		$new_name = md5(time() . serialize($image)); 
		if($upload->upload($new_name)) {
			$info1 = $upload->getFileInfo();
			if($info1) {
				$update['photo'] = '/smiles/'.$info1['name'];
				if($info && $info['photo']) {
					@unlink(BASE_PATH . '/uploads/' . $info['photo']);
				}
			}
		}
		
		$db->update('smiles', $update, array('id = ?' => (int)$id));
		return $id;
	}

	public static function getSmiles($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('smiles')
					->order('name ASC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalSmiles() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('smiles', 'COUNT(id)');
		
		return $db->fetchOne($query);
	}

	public static function getSmile($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('smiles')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	public static function deleteSmile($id) {
		$info = self::getSmile($id);
		if($info && $info['photo']) {
			@unlink(BASE_PATH . '/uploads/' . $info['photo']);
		}
		$db = JO_Db::getDefaultAdapter();
		$db->delete('smiles', array('id = ?' => (int)$id));
	}
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('smiles', array(
			'visible' => new JO_Db_Expr("IF(visible = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
	public static function initDbInstall() {
		$db = JO_Db::getDefaultAdapter();
		$db->query("
			CREATE TABLE IF NOT EXISTS `smiles` (
			  `id` int(11) NOT NULL auto_increment,
			  `name` varchar(255) character set utf8 collate utf8_unicode_ci default NULL,
			  `code` varchar(64) NOT NULL,
			  `photo` varchar(255) character set utf8 collate utf8_unicode_ci default NULL,
			  `visible` enum('true','false') character set utf8 collate utf8_unicode_ci default 'false',
			  `order_index` int(11) NOT NULL default '0',
			  PRIMARY KEY  (`id`)
			) ENGINE=InnoDB;
		");
	} 
	
}

?>