<?php

class Model_Items_Image extends Model_Items {
	
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
		
		
		if(isset($data['free_file'])) {
			$db->update('items', array(
				'free_file' => 'false'
			));
			
			self::addUserStatus($id, 'freefile');
		}
		
		$db->update('items', array(
			'name' => $data['name'],
			'thumbnail' => $main_file,
			'theme_preview_thumbnail' => $main_file,
			'main_file' => $main_file,
			'description' => $data['description'],
			'price' => $data['price'][$data['default_price']],
			'free_file' => isset($data['free_file']) ? 'true' : 'false',
			'item_tags_string' => isset($data['tags']) ? $data['tags'] : '',
//			'demo_url' => $data['demo_url'],
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
	
		$info = self::getItem($id);
		if(!$info) {
			return;
		}
		
		$sizes = Model_Sizes::getAll();
		
		$tmp_sizes = array();
		foreach($sizes AS $size1) {
			$tmp_sizes[$size1['id']] = $size1;
		}
		
		$info_file = getimagesize(BASE_PATH.'/uploads/'.$info['main_file']);
		$steps = 0;
		if($info_file[0] < $info_file[1]) {
			$type = 'p';
			$steps = $info_file[0];
			$source_aspect_ratio = round($info_file[1]/$info_file[0], 5);
		} elseif($info_file[0] > $info_file[1]) {
			$type = 'l';
			$steps = $info_file[1];
			$source_aspect_ratio = round($info_file[0]/$info_file[1], 5);
		} else {
			$type = 'k';
			$steps = $info_file[0];
			$source_aspect_ratio = 1;
		}
		
		$temp_sizes = array();
		$temp_sizes2 = array();
		$deleted = array();
		foreach($data['price'] AS $size_id => $price) {
			if(trim($price) && (float)$price && isset($tmp_sizes[$size_id])) {
				
				$sizeMP = $tmp_sizes[$size_id]['size'] * 1000000;
				$sizeMPFrom = $sizeMP - ($sizeMP/100);
				
				for($i=$steps; $i>=1; $i--) {
					if($type == 'p') {
						$width = ($info_file[0]-$i);
						$height = round( $width * $source_aspect_ratio );
					} elseif($type == 'l') {
						$height = ($info_file[1]-$i);
						$width = round( $height * $source_aspect_ratio );
					} else { 
						$width = ($info_file[0]-$i);
						$height = ($info_file[1]-$i);
					}
					
					if($width < 1 || $height < 1) continue;
					
					if($width*$height >= $sizeMPFrom && $width*$height <= $sizeMP) {
						
						$temp_sizes[$size_id] = array(
							'width' => $width,
							'height' => $height,
							'price' => $price,
							'size_id' => $size_id,
							'size' => $tmp_sizes[$size_id]['size']
						);
						
						$temp_sizes2[$size_id] = $size_id;
							
						continue;
					}
				}
			} else {
				$deleted[] = $size_id;
			}
		}
		
		
		$query_files = $db->select()
							->from('items_prices')
							->where('item_id = ?', (int)$id);
		$list_files = $db->fetchAll($query_files);
		if($list_files) {
			foreach($list_files AS $fils) {
				if(in_array($fils['size_id'], $deleted)) {
					if( !self::fileIsOrdered($id, $fils['size_id']) ) {
						unlink(BASE_PATH.'/uploads/'.$fils['main_file']);
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					} else {
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					}
				} elseif( !in_array($fils['size_id'], $temp_sizes2) ) {
					if( !self::fileIsOrdered($id, $fils['size_id']) ) {
						unlink(BASE_PATH.'/uploads/'.$fils['main_file']);
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					} else {
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					}
				} else {
					if(file_exists(BASE_PATH.'/uploads/'.$fils['main_file'])) {
						if( !self::fileIsOrdered($id, $fils['size_id']) ) {
							unlink(BASE_PATH.'/uploads/'.$fils['main_file']);
							$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
						} else {
							$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
						}
					} else {
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					}
				}
			}
		}
		
		
		$main_file = BASE_PATH.'/uploads/'.$info['main_file'];
		$main_path = dirname($info['main_file']);
		
		foreach($temp_sizes AS $key => $value) {
			$name_new = md5(time() . '_' . mt_rand()) . '.' . round($key,2) . strtolower(strrchr(basename($info['main_file']), '.'));
			
			$ext = strtolower(strrchr($info['main_file'], '.'));
					
			$image_p = imagecreatetruecolor($value['width'], $value['height']);
						
			$image = null;
			if($ext == '.jpg' || $ext == '.jpeg') {
				$image = imagecreatefromjpeg(BASE_PATH.'/uploads/'.$info['main_file']);
			} else {
				continue;
			}
			
			imageantialias( $image_p , true );
			if($image) {
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $value['width'], $value['height'], $info_file[0], $info_file[1]);
			}
			
			if(!file_exists(dirname($main_file).'/downloads/')) {
				mkdir(dirname($main_file).'/downloads/', 0777, true);
			}
			
			imagejpeg($image_p, dirname($main_file).'/downloads/'.$name_new , 100);
			
			if(file_exists(dirname($main_file).'/downloads/'.$name_new)) {
				$db->insert('items_prices', array(
					'item_id' => (int)$id,
					'size_id' => (int)$value['size_id'],
					'price' => (float)$value['price'],
					'main_file' => $main_path.'/downloads/'.$name_new,
					'size' => $key,
					'width' => $value['width'],
					'height' => $value['height'],
					
				));
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
		
		$href = '<a href="'.WM_Router::create($request->getBaseUrl() . '?module='.$info['module'].'&controller=items&item_id=' . $id).'">'.$info['name'].'</a>';
		
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
		
		set_time_limit(0);
		
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
			'price' => $data['price'][$data['default_price']],
			'free_file' => isset($data['free_file']) ? 'true' : 'false',
			'item_tags_string' => isset($data['tags']) ? $data['tags'] : '',
//			'demo_url' => $data['demo_url'],
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
		
		$sizes = Model_Sizes::getAll();
		
		$tmp_sizes = array();
		foreach($sizes AS $size1) {
			$tmp_sizes[$size1['id']] = $size1;
		}
		
		$info_file = getimagesize(BASE_PATH.'/uploads/'.$info['main_file']);
		$steps = 0;
		if($info_file[0] < $info_file[1]) {
			$type = 'p';
			$steps = $info_file[0];
			$source_aspect_ratio = round($info_file[1]/$info_file[0], 5);
		} elseif($info_file[0] > $info_file[1]) {
			$type = 'l';
			$steps = $info_file[1];
			$source_aspect_ratio = round($info_file[0]/$info_file[1], 5);
		} else {
			$type = 'k';
			$steps = $info_file[0];
			$source_aspect_ratio = 1;
		}
		
		$temp_sizes = array();
		$temp_sizes2 = array();
		$deleted = array();
		foreach($data['price'] AS $size_id => $price) {
			if(trim($price) && (float)$price && isset($tmp_sizes[$size_id])) {
				
				$sizeMP = $tmp_sizes[$size_id]['size'] * 1000000;
				$sizeMPFrom = $sizeMP - ($sizeMP/100);
				
				for($i=$steps; $i>=1; $i--) {
					if($type == 'p') {
						$width = ($info_file[0]-$i);
						$height = round( $width * $source_aspect_ratio );
					} elseif($type == 'l') {
						$height = ($info_file[1]-$i);
						$width = round( $height * $source_aspect_ratio );
					} else { 
						$width = ($info_file[0]-$i);
						$height = ($info_file[1]-$i);
					}
					
					if($width < 1 || $height < 1) continue;
					
					if($width*$height >= $sizeMPFrom && $width*$height <= $sizeMP) {
						
						$temp_sizes[$size_id] = array(
							'width' => $width,
							'height' => $height,
							'price' => $price,
							'size_id' => $size_id,
							'size' => $tmp_sizes[$size_id]['size']
						);
						
						$temp_sizes2[$size_id] = true;
							
						continue;
					}
				}
			} else {
				$deleted[] = $size_id;
			}
		}
		
		
		$query_files = $db->select()
							->from('items_prices')
							->where('item_id = ?', (int)$id);
		$list_files = $db->fetchAll($query_files);
		if($list_files) {
			foreach($list_files AS $fils) {
				if(in_array($fils['size_id'], $deleted)) {
					if( !self::fileIsOrdered($id, $fils['size_id']) ) {
						unlink(BASE_PATH.'/uploads/'.$fils['main_file']);
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					} else {
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					}
				} elseif( !in_array($fils['size_id'], $temp_sizes2) ) {
					if( !self::fileIsOrdered($id, $fils['size_id']) ) {
						unlink(BASE_PATH.'/uploads/'.$fils['main_file']);
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					} else {
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					}
				} else {
					if(file_exists(BASE_PATH.'/uploads/'.$fils['main_file'])) {
						$db->update('items_prices', array(
							'price' => (float)$temp_sizes[$fils['size_id']]['price']
						), array('id = ?' => (int)$fils['id']));
						unset($temp_sizes[$fils['size_id']]);
					} else {
						$db->delete('items_prices', array('id = ?' => (int)$fils['id']));
					}
				}
			}
		}
		
		
		$main_file = BASE_PATH.'/uploads/'.$info['main_file'];
		$main_path = dirname($info['main_file']);
		
		foreach($temp_sizes AS $key => $value) {
			$name_new = md5(time() . '_' . mt_rand()) . '.' . round($key,2) . strtolower(strrchr(basename($info['main_file']), '.'));
			
			$ext = strtolower(strrchr($info['main_file'], '.'));
					
			$image_p = imagecreatetruecolor($value['width'], $value['height']);
						
			$image = null;
			if($ext == '.jpg' || $ext == '.jpeg') {
				$image = imagecreatefromjpeg(BASE_PATH.'/uploads/'.$info['main_file']);
			} else {
				continue;
			}
			
			imageantialias( $image_p , true );
			if($image) {
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $value['width'], $value['height'], $info_file[0], $info_file[1]);
			}
			
			if(!file_exists(dirname($main_file).'/downloads/')) {
				mkdir(dirname($main_file).'/downloads/', 0777, true);
			}
			
			imagejpeg($image_p, dirname($main_file).'/downloads/'.$name_new , 100);
			
			if(file_exists(dirname($main_file).'/downloads/'.$name_new)) {
				$db->insert('items_prices', array(
					'item_id' => (int)$id,
					'size_id' => (int)$value['size_id'],
					'price' => (float)$value['price'],
					'main_file' => $main_path.'/downloads/'.$name_new,
					'size' => $key,
					'width' => $value['width'],
					'height' => $value['height'],
					
				));
			}
		}
		
		
		
		
	}
	
	/////////////////////// help
	
	public static function fileIsOrdered($item_id, $size_id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->fetchOne($db->select()->from('orders', 'COUNT(id)')->where('item_id=?',(int)$item_id)->where('size_id=?',(int)$size_id)->where('paid=?','true')->where('type=?','buy'));
	}
	
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
	
	public static function getPriceItem($id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('items_prices', array('size_id', 'price'))
					->where('item_id = ?', (int)$id);
		return $db->fetchPairs($query);
	}

}

?>