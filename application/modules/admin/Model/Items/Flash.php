<?php

class Model_Items_Flash extends Model_Items {
	
	public static function editItemUpdate($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getItem($id);
		if(!$info) {
			return;
		}
		
		$info_update = self::getItemUpdate($id);
		if(!$info_update) {
			return;
		}
		
		///thumb
		$thumb = $info['thumbnail'];
		$model_images = new Model_Images();
		if($info_update['thumbnail'] && file_exists(BASE_PATH . '/uploads/' . $info_update['thumbnail'])) {
			if($info['thumbnail'] && file_exists(BASE_PATH . '/uploads/' . $info['thumbnail'])) {
				$model_images->deleteImages($info['thumbnail'], true);
			}
			if(copy(BASE_PATH . '/uploads/' . $info_update['thumbnail'], BASE_PATH . '/uploads/' . str_replace('/temp/','/',$info_update['thumbnail']))) {
				$thumb = str_replace('/temp/','/',$info_update['thumbnail']);
			}
		}
		
		///theme_preview_thumbnail
		$theme_preview_thumbnail = $info['theme_preview_thumbnail'];
		if($info_update['theme_preview_thumbnail'] && file_exists(BASE_PATH . '/uploads/' . $info_update['theme_preview_thumbnail'])) {
			if($info['theme_preview_thumbnail'] && file_exists(BASE_PATH . '/uploads/' . $info['theme_preview_thumbnail'])) {
				$model_images->deleteImages($info['theme_preview_thumbnail'], true);
			}
			if(copy(BASE_PATH . '/uploads/' . $info_update['theme_preview_thumbnail'], BASE_PATH . '/uploads/' . str_replace('/temp/','/',$info_update['theme_preview_thumbnail']))) {
				$theme_preview_thumbnail = str_replace('/temp/','/',$info_update['theme_preview_thumbnail']);
			}
		}
		
		///theme_preview 
		$theme_preview = $info['theme_preview'];
		if($info_update['theme_preview'] && file_exists(BASE_PATH . '/uploads/' . $info_update['theme_preview'])) {
			if($info['theme_preview'] && file_exists(BASE_PATH . '/uploads/' . $info['theme_preview'])) {
				unlink($info['theme_preview']);
			}
			if(copy(BASE_PATH . '/uploads/' . $info_update['theme_preview'], BASE_PATH . '/uploads/' . str_replace('/temp/','/',$info_update['theme_preview']))) {
				$theme_preview = str_replace('/temp/','/',$info_update['theme_preview']);
			}
		}
		
		///main_file 
		$main_file = $info['main_file'];
		if($info_update['main_file'] && file_exists(BASE_PATH . '/uploads/' . $info_update['main_file'])) {
			if($info['main_file'] && file_exists(BASE_PATH . '/uploads/' . $info['main_file'])) {
				unlink($info['main_file']);
			}
			if(copy(BASE_PATH . '/uploads/' . $info_update['main_file'], BASE_PATH . '/uploads/' . str_replace('/temp/','/',$info_update['main_file']))) {
				$main_file = str_replace('/temp/','/',$info_update['main_file']);
			}
		}
		
    	///video_file 
		$video_file = $info['video_file'];
		if($info_update['video_file'] && file_exists(BASE_PATH . '/uploads/' . $info_update['video_file'])) {
			if($info['video_file'] && file_exists(BASE_PATH . '/uploads/' . $info['video_file'])) {
				unlink($info['video_file']);
			}
			if(copy(BASE_PATH . '/uploads/' . $info_update['video_file'], BASE_PATH . '/uploads/' . str_replace('/temp/','/',$info_update['video_file']))) {
				$video_file = str_replace('/temp/','/',$info_update['video_file']);
			}
		}
		
		//preview
		$path = BASE_PATH . '/uploads/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/', true)->toString() . $id . '/temp/preview/';
		$path2 = BASE_PATH . '/uploads/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/', true)->toString() . $id . '/preview/';
		if(file_exists($path) && is_dir($path)) {
			if(file_exists($path2) && is_dir($path2)) {
				self::unlink($path2, false);
			}
			self::recursiveCopy($path, $path2);
		}
		
		
		if(isset($data['free_file'])) {
			$db->update('items', array(
				'free_file' => 'false'
			));
			
			self::addUserStatus($id, 'freefile');
		}
		
		$db->update('items', array(
			'name' => $data['name'],
			'thumbnail' => $thumb,
			'theme_preview_thumbnail' => $theme_preview_thumbnail,
			'theme_preview' => $theme_preview,
			'main_file' => $main_file,
		    'video_file'	=>    $video_file,
			'description' => $data['description'],
			'price' => $data['price'],
			'free_file' => isset($data['free_file']) ? 'true' : 'false',
		    'preview'	=> ($data['preview']==1) ? '1' : '0',
			'weekly_from' => ( $data['weekly_from'] ? JO_Date::getInstance($data['weekly_from'], 'yy-mm-dd', true) : '0000-00-00' ),
			'weekly_to' => ( $data['weekly_to'] ? JO_Date::getInstance($data['weekly_to'], 'yy-mm-dd', true) : '0000-00-00' )
		), array('id = ?' => (int)$id));
		
		if(isset($data['set_status']) && $data['set_status'] == 'active') {
			$db->update('items', array(
				'status' => $data['set_status']
			), array('id = ?' => (int)$id));
		}
		
		if(isset($data['weekly_to']) && trim($data['weekly_to']) != '') {
			self::addUserStatus($id, 'featured');
		}
		
		$db->delete('items_to_category', array('item_id = ?' => (int)$id));
		if(isset($data['category_id'])) {
			foreach($data['category_id'] AS $category_id) {
				$categories = Model_Categories::getCategoryParents(Model_Categories::getCategories(array('filter_id_key' => true)), $category_id);
				$categories = explode(',', $categories);
				array_pop($categories);
				$categories = array_reverse($categories);
				$categories = ','.implode(',', $categories).',';
				$db->insert('items_to_category', array(
					'item_id' => (int)$id,
					'categories' => $categories
				));
			}
		}
		
		$db->delete('items_attributes', array('item_id = ?' => (int)$id));
		if(isset($data['attributes']) && is_array($data['attributes'])) {
			foreach($data['attributes'] AS $cid => $value) {
				if(is_array($value)) {
					foreach($value AS $val) {
						$db->insert('items_attributes', array(
							'item_id' => $id,
							'attribute_id' => $val,
							'category_id' => (int)$cid
						));
					}
				} elseif($value) {
					$db->insert('items_attributes', array(
						'item_id' => $id,
						'attribute_id' => $value,
						'category_id' => (int)$cid
					));
				}
			}
		}
		
		$db->delete('items_tags', array('item_id = ?' => (int)$id));
		if(isset($data['tags']) && $data['tags']) {
			$tags = explode(',', $data['tags']);
			foreach($tags AS $tag) {
				$tag = trim($tag);
				if($tag) {
					$tag_id = Model_Tags::getTagByTitleAndInsert($tag);
					if($tag_id) {
						$db->insert('items_tags', array(
							'item_id' => $id,
							'tag_id' => (int)$tag_id,
							'type' => ''
						));
					}
				}
			}
		}
		
		
		
		/////////// send email
			
		$request = JO_Request::getInstance();
		$translate = JO_Translate::getInstance();
		
		$not_template = Model_Notificationtemplates::get('queue_update');
		$mail = new JO_Mail;
		if(JO_Registry::get('mail_smtp')) {
			$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
		}
		$mail->setFrom('no-reply@'.$request->getDomain());
		
		$href = '<a href="'.WM_Router::create($request->getBaseUrl() . '?controller=items&item_id=' . $id).'">'.$info['name'].'</a>';
		
		if($not_template) {
			$title = $not_template['title'];
			$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
			$html = str_replace('{USERNAME}', $info['username'], $html);
			$html = str_replace('{ITEM}', $info['name'], $html);
			$html = str_replace('{URL}', $href, $html);
		} else {
			$title = "[".$request->getDomain()."] " . $data['name'];
			$html = nl2br($translate->translate('Item') .' 
			' . $href . ' ' . $translate->translate('is updated'));
		}
		
		$mail->setSubject($title);
		$mail->setHTML($html);
		$result = $mail->send(array($info['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
		unset($mail);
		
		//////////////////////
		
		self::unlink(BASE_PATH . '/uploads/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $id . '/temp/');
		self::unlink(BASE_PATH . '/uploads/cache/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $id . '/');
		$db->delete('temp_items', array('item_id = ?' => (int)$id));
		$db->delete('temp_items_tags', array('item_id = ?' => (int)$id));
		
	}
	
	public function editItem($id, $data) {
		$db = JO_Db::getDefaultAdapter();
	
		$info = self::getItem($id);
		if(!$info) {
			return;
		}
		
		if(isset($data['free_file'])) {
			$db->update('items', array(
				'free_file' => 'false'
			));
			
			self::addUserStatus($id, 'freefile');
		}
		
		$db->update('items', array(
			'name' => $data['name'],
			'description' => $data['description'],
			'price' => $data['price'],
			'free_file' => isset($data['free_file']) ? 'true' : 'false',
		'preview'	=> ($data['preview']==1) ? '1' : '0',
			'weekly_from' => ( $data['weekly_from'] ? JO_Date::getInstance($data['weekly_from'], 'yy-mm-dd', true) : '0000-00-00' ),
			'weekly_to' => ( $data['weekly_to'] ? JO_Date::getInstance($data['weekly_to'], 'yy-mm-dd', true) : '0000-00-00' )
		), array('id = ?' => (int)$id));
		
		if(isset($data['set_status']) && $data['set_status'] == 'active') {
			$db->update('items', array(
				'status' => $data['set_status']
			), array('id = ?' => (int)$id));
			
			/////////// send email
			
			$request = JO_Request::getInstance();
			$translate = JO_Translate::getInstance();
			
			$not_template = Model_Notificationtemplates::get('approval_item');
			$mail = new JO_Mail;
			if(JO_Registry::get('mail_smtp')) {
				$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
			}
			$mail->setFrom('no-reply@'.$request->getDomain());
			
			$href = '<a href="'.WM_Router::create($request->getBaseUrl() . '?controller=items&item_id=' . $id).'">'.$info['name'].'</a>';
			
			if($not_template) {
				$title = $not_template['title'];
				$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
				$html = str_replace('{USERNAME}', $info['username'], $html);
				$html = str_replace('{ITEM}', $info['name'], $html);
				$html = str_replace('{URL}', $href, $html);
			} else {
				$title = "[".$request->getDomain()."] " . $data['name'];
				$html = nl2br($translate->translate('Item') .' 
				' . $href . ' ' . $translate->translate('approval'));
			}
			
			$mail->setSubject($title);
			$mail->setHTML($html);
			$result = $mail->send(array($info['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
			unset($mail);
			
			//////////////////////
		}
		
		if(isset($data['weekly_to']) && trim($data['weekly_to']) != '') {
			self::addUserStatus($id, 'featured');
		}
		
		$db->delete('items_to_category', array('item_id = ?' => (int)$id));
		if(isset($data['category_id'])) {
			foreach($data['category_id'] AS $category_id) {
				$categories = Model_Categories::getCategoryParents(Model_Categories::getCategories(array('filter_id_key' => true)), $category_id);
				$categories = explode(',', $categories);
				array_pop($categories);
				$categories = array_reverse($categories);
				$categories = ','.implode(',', $categories).',';
				$db->insert('items_to_category', array(
					'item_id' => (int)$id,
					'categories' => $categories
				));
			}
		}
		
		$db->delete('items_attributes', array('item_id = ?' => (int)$id));
		if(isset($data['attributes']) && is_array($data['attributes'])) {
			foreach($data['attributes'] AS $cid => $value) {
				if(is_array($value)) {
					foreach($value AS $val) {
						$db->insert('items_attributes', array(
							'item_id' => $id,
							'attribute_id' => $val,
							'category_id' => (int)$cid
						));
					}
				} elseif($value) {
					$db->insert('items_attributes', array(
						'item_id' => $id,
						'attribute_id' => $value,
						'category_id' => (int)$cid
					));
				}
			}
		}
		
		$db->delete('items_tags', array('item_id = ?' => (int)$id));
		if(isset($data['tags']) && $data['tags']) {
			$tags = explode(',', $data['tags']);
			foreach($tags AS $tag) {
				$tag = trim($tag);
				if($tag) {
					$tag_id = Model_Tags::getTagByTitleAndInsert($tag);
					if($tag_id) {
						$db->insert('items_tags', array(
							'item_id' => $id,
							'tag_id' => (int)$tag_id,
							'type' => ''
						));
					}
				}
			}
		}
		
	}
	
	/////////////////////// help
	
	private static function addUserStatus($id, $type='freefile') {
		$item = self::getItem($id);
		if($item) {
			if(!self::isExistUserStatus($item['user_id'], $type)) {
				self::insertUserStatus($item['user_id'], $type);
			}
		}		
		return true;
	}
	
	private static function isExistUserStatus($id, $type) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('users_status', 'COUNT(id)')
					->where('user_id = ?', (int)$id)
					->where('status = ?', $type);
		
		return $db->fetchOne($query);
	}
	
	private static function insertUserStatus($id, $type) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->insert('users_status', array(
			'user_id' => (int)$id,
			'status' => $type,
			'datetime' => new JO_Db_Expr('NOW()')
		));
		
		return true;
	}
	
	////////////////////
	
	public static function unlink($dir, $deleteRootToo=true) {
		$dir = rtrim($dir, '/');
	    if(!$dh = @opendir($dir)) {
	        return;
	    }
	    $model_images = new Model_Images();
    	while (false !== ($obj = readdir($dh))) {
	        if($obj == '.' || $obj == '..') {
	            continue;
	        }
	        if (!@unlink($dir . '/' . $obj)) {
	        	if(is_file($dir . '/' . $obj)) {
	        		$model_images->deleteImages($dir . '/' . $obj, true);
	        	}
	            self::unlink($dir.'/'.$obj, true);
	        }
    	}
    	closedir($dh);
	    if ($deleteRootToo) {
	        @rmdir($dir);
	    }

	    return;
	}
	
	private static function recursiveCopy($source, $destination) { 
		$source = rtrim($source, '/');
		$destination = rtrim($destination, '/');
		$directory = opendir($source); 
		
		@mkdir($destination, 0777, true);
		
		while (false !== ($file = readdir($directory))) {
			if (($file != '.') && ($file != '..')) { 
				if (is_dir($source . '/' . $file)) { 
					self::recursiveCopy($source . '/' . $file, $destination . '/' . $file); 
				} else { 
					copy($source . '/' . $file, $destination . '/' . $file); 
				} 
			} 
		} 
		
		closedir($directory); 
	} 

}

?>