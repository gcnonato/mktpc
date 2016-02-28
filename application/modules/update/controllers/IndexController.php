<?php 

class IndexController extends JO_Action {
	
	public function init() {
		set_time_limit(0);
		$this->noLayout(true);
	}
	
    public function indexAction() {
    	$request = $this->getRequest();
		
		$update_for = 'themes';
		
		$db = JO_Db::getDefaultAdapter();
		
		$this->view->msg_error = array();
		$this->view->msg_success = array();
		
		//begin updater
		$old_sys_config = BASE_PATH . '/old_sys/data/uploads/language/config.php';
		
		if(!file_exists($old_sys_config)) {
			$this->view->msg_error['old_sys'] = 'Please move the "data" folder of your old system into folder: <strong>' . BASE_PATH . '/old_sys/</strong>';
		}
		
		if(!$this->view->msg_error && $request->isPost()) {
			
			self::unlink(BASE_PATH . '/uploads/items/', true);
			self::unlink(BASE_PATH . '/uploads/attributes/', true);
			self::unlink(BASE_PATH . '/uploads/cache/', true);
			self::unlink(BASE_PATH . '/uploads/countries/', true);
			
			$db_queries = array();
			
			include_once $old_sys_config;
			
			$old_config = $db->getConfig();
			
			$db_config = $old_config;
			$db_config['host'] = $configArr['mysql_host'];
			$db_config['username'] = $configArr['mysql_user'];
			$db_config['password'] = $configArr['mysql_pass'];
			$db_config['dbname'] = $configArr['mysql_db'];
			
			$old_db_tables = $db->listTables();
			
			$new_db = JO_Db::setAdapterConfig($db_config);
			
			$new_db_tables = $new_db->listTables();
			
			
			// other tables
			$all_arr = array('bulletin','bulletin_emails','collections','collections_rates','contacts','contacts_categories','deposit','history','percents','quiz','quiz_answers','users_emails','users_followers','users_referals_count','users_status','user_groups','withdraw', 'items_attributes','items_collections','items_comments','items_faqs','items_rates','items_tags','items_to_category');
			
			
			foreach($all_arr AS $table) {
				
				if(!in_array($table, $old_db_tables) || !in_array($table, $new_db_tables)) continue;
				
				$query = $new_db->select()
								->from($table);
				
				
				$attributes_categories = $new_db->fetchAll($query);
				if($attributes_categories) {
					$db_queries['TRUNCATE'][] = array(
						'table' => $table,
						'where' => null
					);
					foreach($attributes_categories AS $key => $val) {
						$vals = array();
						foreach($val AS $k => $d) {
							if(self::is_serialized( $d )) {
								$vals[$k] = $d;
							} else {
								$vals[$k] = htmlspecialchars($d, ENT_QUOTES, 'utf-8');
							}
						}
						
						if(count($vals) > 0) {
							$db_queries[$table][] = $vals;
						}
						
					}
				}
			}
			
			//attributes_categories
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `attributes_categories`");
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'attributes_categories',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
//					$db_queries[] = "INSERT INTO `attributes_categories` (`id`, `name`, `type`, `categories`, `visible`, `order_index`,`required`) VALUES ('".(int)$data['id'] ."', '".htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8') ."', '".htmlspecialchars($data['type'], ENT_QUOTES, 'utf-8') ."', '".htmlspecialchars($data['categories'], ENT_QUOTES, 'utf-8') ."', '".htmlspecialchars($data['visible'], ENT_QUOTES, 'utf-8') ."', '".(int)$data['order_index'] ."',1);";
					$db_queries['attributes_categories'][] = array(
						'id' => (int)$data['id'],
						'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8'),
						'type' => htmlspecialchars($data['type'], ENT_QUOTES, 'utf-8'),
						'categories' => htmlspecialchars($data['categories'], ENT_QUOTES, 'utf-8'),
						'visible' => 'true',
						'order_index' => (int)$data['order_index'],
						'required' => 1
					);
				}
			}
			//attributes
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `attributes`");
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'attributes',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
					$photo = self::copyFile(BASE_PATH . '/old_sys/data/uploads/attributes/'.$data['photo'], '/attributes/'.$data['photo']);
					$db_queries['attributes'][] = array(
						'id' => (int)$data['id'], 
						'category_id' => (int)$data['category_id'], 
						'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8'), 
						'photo' => basename($photo), 
						'visible' => 'true',
						'order_index' => (int)$data['order_index']
					);
				}
			}
			//attributes
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `badges`");
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'badges',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
					$photo = self::copyFile(BASE_PATH . '/old_sys/data/uploads/badges/'.$data['photo'], '/badges/'.$data['photo']);
					$db_queries['badges'][] = array(
						'id' => (int)$data['id'], 
						'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8'), 
						'photo' => basename($photo), 
						'visible' => htmlspecialchars($data['visible'], ENT_QUOTES, 'utf-8'), 
						'from' => htmlspecialchars($data['from'], ENT_QUOTES, 'utf-8'), 
						'to' => htmlspecialchars($data['to']), 
						'type' => htmlspecialchars($data['type'], ENT_QUOTES, 'utf-8'), 
						'sys_key' => htmlspecialchars($data['sys_key'], ENT_QUOTES, 'utf-8')
					);
				}
			}
			//categories
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `categories`");
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'categories',
						'where' => null
					);
				$db_queries['TRUNCATE'][] = array(
						'table' => 'categories_description',
						'where' => null
					);	
				
				foreach($attributes_categories AS $data) {
					$db_queries['categories'][] = array(
						'id' => (int)$data['id'], 
						'sub_of' => (int)$data['sub_of'],
						'meta_title' => htmlspecialchars($data['meta_title'], ENT_QUOTES, 'utf-8'),
						'meta_keywords' => htmlspecialchars($data['meta_keywords'], ENT_QUOTES, 'utf-8'),
						'meta_description' => htmlspecialchars($data['meta_description'], ENT_QUOTES, 'utf-8'),
						'visible' => htmlspecialchars($data['visible'], ENT_QUOTES, 'utf-8'),
						'order_index' => (int)$data['order_index'],
						'module' => $update_for
					);
					
					$db_queries['categories_description'][] = array(
						'id' => (int)$data['id'], 
						'lid' => 1,
						'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8')
					);
				}
			}
			//countries
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `countries`");
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'countries',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
					$photo = self::copyFile(BASE_PATH . '/old_sys/data/uploads/countries/'.$data['photo'], '/countries/'.$data['photo']);
					$db_queries['countries'][] = array(
						'id' => (int)$data['id'], 
						'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8'), 
						'photo' => basename($photo), 
						'visible' => htmlspecialchars($data['visible'], ENT_QUOTES, 'utf-8'), 
						'order_index' => (int)$data['order_index']
					);
				}
			}
			//items
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `items`");
			$temp_items = array();
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'items',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
					
					$weekly_from = '0000-00-00';
					$weekly_to = '0000-00-00';
					if($data['weekly_to'] && $data['weekly_to'] != '0000-00-00') {
						$weekly_from = $data['weekly_to'];
						$weekly_to = JO_Date::getInstance($data['weekly_to'], 'yy-mm-dd', true)
						->setInterval('+7 days')->toString();
					}
					
					$old_path = BASE_PATH . '/old_sys/data/uploads/items/'.$data['id'].'/';
					$item_path = '/items/' . JO_Date::getInstance($data['datetime'], 'yy/mm/',true)->toString() .$data['id'].'/';
					
					//self::recursiveCopy($old_path . 'preview/', BASE_PATH . '/uploads/' . $item_path . 'preview/');
					$thumbnail = self::copyFile($old_path.$data['thumbnail'], $item_path.$data['thumbnail']);
					$main_file = self::copyFile($old_path.$data['main_file'], $item_path.$data['main_file']);
					$theme_preview = self::copyFile($old_path.$data['theme_preview'], $item_path.$data['theme_preview']);
					$theme_preview_thumbnail = self::copyFromArchive($theme_preview);
					
					$temp_items[(int)$data['id']] = array(
						'id' => (int)$data['id'], 
						'user_id' => (int)$data['user_id'], 
						'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8'), 
						'description' => htmlspecialchars($data['description'], ENT_QUOTES, 'utf-8'),
						'thumbnail' => $thumbnail, 	
						'theme_preview_thumbnail' => $theme_preview_thumbnail, 	
						'theme_preview' => $theme_preview, 	
						'main_file' => $main_file, 	
						'main_file_name' => htmlspecialchars($data['main_file_name'], ENT_QUOTES, 'utf-8'), 	
						'categories' => htmlspecialchars($data['categories'], ENT_QUOTES, 'utf-8'), 	
						'demo_url' => htmlspecialchars($data['demo_url'], ENT_QUOTES, 'utf-8'), 	
						'price' => (float)$data['price'], 	
						'suggested_price' => (float)$data['suggested_price'], 	
						'sales' => (float)$data['sales'], 	
						'earning' => (float)$data['earning'], 	
						'rating' => (float)$data['rating'], 	
						'votes' => (float)$data['votes'], 	
						'score' => (float)$data['score'], 	
						'comments' => (float)$data['comments'], 	
						'free_request' => htmlspecialchars($data['free_request'], ENT_QUOTES, 'utf-8'), 	
						'free_file' => htmlspecialchars($data['free_file'], ENT_QUOTES, 'utf-8'), 	
						'weekly_from' => $weekly_from, 	
						'weekly_to' => $weekly_to, 	
						'reviewer_comment' => htmlspecialchars($data['reviewer_comment'], ENT_QUOTES, 'utf-8'), 	
						'datetime' => htmlspecialchars($data['datetime'], ENT_QUOTES, 'utf-8'), 	
						'status' => htmlspecialchars($data['status'], ENT_QUOTES, 'utf-8'), 	
						'module' => $update_for, 	
						'video_file' => htmlspecialchars(isset($data['video_file']) ? $data['video_file'] : '', ENT_QUOTES, 'utf-8'), 	
						'item_tags_string' => htmlspecialchars(isset($data['item_tags_string']) ? $data['item_tags_string'] : '', ENT_QUOTES, 'utf-8'), 	
						'preview' => htmlspecialchars(isset($data['preview']) ? $data['preview'] : '', ENT_QUOTES, 'utf-8')
					);
					
					$db_queries['items'][] = $temp_items[(int)$data['id']];
				}
			}
			//orders
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `orders`");
			if($attributes_categories) {
				JO_Db::setAdapterConfig($old_config);
				$db_queries['TRUNCATE'][] = array(
						'table' => 'orders',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
					$db_queries['orders'][] = array(
						'id' => (int)$data['id'], 
						'order_id' => (int)(isset($data['order_id']) ? $data['order_id'] : 0), 
						'user_id' => (int)$data['user_id'], 
						'owner_id' => (int)$data['owner_id'], 
						'item_id' => (int)$data['item_id'], 
						'item_name' => htmlspecialchars($data['item_name'], ENT_QUOTES, 'utf-8'), 
						'price' => (float)$data['price'], 
						'receive' => (float)$data['receive'], 
						'datetime' => htmlspecialchars($data['datetime'], ENT_QUOTES, 'utf-8'), 
						'paid' => htmlspecialchars($data['paid'], ENT_QUOTES, 'utf-8'), 
						'paid_datetime' => htmlspecialchars($data['paid_datetime'], ENT_QUOTES, 'utf-8'), 
						'extended' => htmlspecialchars($data['extended'], ENT_QUOTES, 'utf-8'), 
						'type' => htmlspecialchars($data['type'], ENT_QUOTES, 'utf-8'), 
						'currency_code' => WM_Currency::getCurrencyCode(), 
						'currency_value' => '1.00000000', 
						'domain' => htmlspecialchars(isset($data['domain']) ? $data['domain'] : '', ENT_QUOTES, 'utf-8'), 
						'module' => $update_for, 
						'size_id' => htmlspecialchars(isset($data['size_id']) ? $data['size_id'] : '', ENT_QUOTES, 'utf-8'), 
						'main_file' => (isset($temp_items[$data['item_id']]['main_file']) ? $temp_items[$data['item_id']]['main_file'] : ''), 
						'main_file_info' => htmlspecialchars(isset($data['main_file_info']) ? $data['main_file_info'] : '', ENT_QUOTES, 'utf-8')
					);
				}
			}
			$new_db = JO_Db::setAdapterConfig($db_config);
			
			//temp_items
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `temp_items`");
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'temp_items',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
					
					if(!isset($temp_items[$data['item_id']])) {
						continue;
					}
					
					$old_path = BASE_PATH . '/old_sys/data/uploads/items/'.$data['item_id'].'/temp/';
					$item_path = '/items/' . JO_Date::getInstance($data['datetime'], 'yy/mm/',true)->toString() .$data['item_id'].'/temp/';
					
					//self::recursiveCopy($old_path . 'preview/', BASE_PATH . '/uploads/' . $item_path . 'preview/');
					$thumbnail = self::copyFile($old_path.$data['thumbnail'], $item_path.$data['thumbnail']);
					$main_file = self::copyFile($old_path.$data['main_file'], $item_path.$data['main_file']);
					$theme_preview = self::copyFile($old_path.$data['theme_preview'], $item_path.$data['theme_preview']);
					$theme_preview_thumbnail = self::copyFromArchive($theme_preview);
					
					$db_queries['temp_items'][] = array(
						'id' => (int)$data['id'], 
						'item_id' => (int)$data['item_id'],
						'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8'), 
						'thumbnail' => $thumbnail, 	
						'theme_preview_thumbnail' => $theme_preview_thumbnail, 	
						'theme_preview' => $theme_preview, 	
						'main_file' => $main_file, 	
						'main_file_name' => htmlspecialchars($data['main_file_name'], ENT_QUOTES, 'utf-8'), 		
						'reviewer_comment' => htmlspecialchars($data['reviewer_comment'], ENT_QUOTES, 'utf-8'), 	
						'datetime' => htmlspecialchars($data['datetime'], ENT_QUOTES, 'utf-8'), 	
						'video_file' => htmlspecialchars(isset($data['video_file']) ? $data['video_file'] : '', ENT_QUOTES, 'utf-8'),	
						'preview' => htmlspecialchars(isset($data['preview']) ? $data['preview'] : '', ENT_QUOTES, 'utf-8')
					);
				}
			}
			//temp_items_tags
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `temp_items_tags`");
			if($attributes_categories) {
				foreach($attributes_categories AS $data) {
					
					if(!isset($temp_items[$data['item_id']])) {
						continue;
					}
					
					$db_queries['TRUNCATE'][] = array(
						'table' => 'items_tags',
						'where' => "`item_id` = '".(int)$data['item_id']."'"
					);
					
					$db_queries['items_tags'][] = array(
						'item_id' => (int)$data['item_id'], 
						'item_id' => (int)$data['item_id'],
						'type' => htmlspecialchars($data['type'], ENT_QUOTES, 'utf-8')
					);
				}
			}
			
			/*
			if(in_array('slider', $old_db_tables) && in_array('slider', $new_db_tables)) {
				//slider
				$attributes_categories = $new_db->fetchAll("SELECT * FROM `slider`");
				if($attributes_categories) {
					$db_queries['TRUNCATE'][] = array(
						'table' => 'slider',
						'where' => null
					);
					foreach($attributes_categories AS $data) {
						$photo = self::copyFile(BASE_PATH . '/old_sys/data/uploads/slider/'.$data['photo'], '/slider/'.$data['photo']);
						$db_queries['slider'][] = array(
							'id' => (int)$data['id'], 
							'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8'), 
							'url' => htmlspecialchars($data['url'], ENT_QUOTES, 'utf-8'), 
							'photo' => ($photo), 
							'visible' => htmlspecialchars($data['visible'], ENT_QUOTES, 'utf-8'), 
							'order_index' => (int)$data['order_index']
						);
					}
				}
			} */
			
			//users
			$attributes_categories = $new_db->fetchAll("SELECT * FROM `users`");
			if($attributes_categories) {
				$db_queries['TRUNCATE'][] = array(
						'table' => 'users',
						'where' => null
					);
				foreach($attributes_categories AS $data) {
					
					$old_path = BASE_PATH . '/old_sys/data/uploads/users/'.$data['user_id'].'/';
					$item_path = '/users/' . JO_Date::getInstance($data['register_datetime'], 'yy/mm/',true)->toString() .$data['user_id'].'/';
					
					$avatar = self::copyFile($old_path.$data['avatar'], $item_path.$data['avatar']);
					$homeimage = self::copyFile($old_path.$data['homeimage'], $item_path.$data['homeimage']);
					
					$db_queries['users'][] = array(
						'user_id' => (int)$data['user_id'], 
						'username' => htmlspecialchars($data['username'], ENT_QUOTES, 'utf-8'), 
						'password' => htmlspecialchars($data['password'], ENT_QUOTES, 'utf-8'), 
						'email' => htmlspecialchars($data['email'], ENT_QUOTES, 'utf-8'), 
						'firstname' => htmlspecialchars($data['firstname'], ENT_QUOTES, 'utf-8'), 
						'lastname' => htmlspecialchars($data['lastname'], ENT_QUOTES, 'utf-8'), 
						'featured_item_id' => htmlspecialchars($data['featured_item_id'], ENT_QUOTES, 'utf-8'), 
						'exclusive_author' => htmlspecialchars($data['exclusive_author'], ENT_QUOTES, 'utf-8'), 
						'license' => $data['license'], 
						'avatar' => $avatar, 
						'homeimage' => $homeimage, 
						'firmname' => htmlspecialchars($data['firmname'], ENT_QUOTES, 'utf-8'), 
						'profile_title' => htmlspecialchars($data['profile_title'], ENT_QUOTES, 'utf-8'), 
						'profile_desc' => htmlspecialchars($data['profile_desc'], ENT_QUOTES, 'utf-8'), 
						'live_city' => htmlspecialchars($data['live_city'], ENT_QUOTES, 'utf-8'), 
						'country_id' => htmlspecialchars($data['country_id'], ENT_QUOTES, 'utf-8'), 
						'freelance' => htmlspecialchars($data['freelance'], ENT_QUOTES, 'utf-8'), 
						'social' => $data['social'], 
						'quiz' => htmlspecialchars($data['quiz'], ENT_QUOTES, 'utf-8'), 
						'deposit' => htmlspecialchars($data['deposit'], ENT_QUOTES, 'utf-8'), 
						'earning' => htmlspecialchars($data['earning'], ENT_QUOTES, 'utf-8'), 
						'total' => htmlspecialchars($data['total'], ENT_QUOTES, 'utf-8'), 
						'sold' => htmlspecialchars($data['sold'], ENT_QUOTES, 'utf-8'), 
						'items' => htmlspecialchars($data['items'], ENT_QUOTES, 'utf-8'), 
						'sales' => htmlspecialchars($data['sales'], ENT_QUOTES, 'utf-8'), 
						'buy' => htmlspecialchars($data['buy'], ENT_QUOTES, 'utf-8'), 
						'rating' => htmlspecialchars($data['rating'], ENT_QUOTES, 'utf-8'), 
						'score' => htmlspecialchars($data['score'], ENT_QUOTES, 'utf-8'), 
						'votes' => htmlspecialchars($data['votes'], ENT_QUOTES, 'utf-8'), 
						'referals' => htmlspecialchars($data['referals'], ENT_QUOTES, 'utf-8'), 
						'referal_money' => htmlspecialchars($data['referal_money'], ENT_QUOTES, 'utf-8'), 
						'featured_author' => htmlspecialchars($data['featured_author'], ENT_QUOTES, 'utf-8'), 
						'register_datetime' => htmlspecialchars($data['register_datetime'], ENT_QUOTES, 'utf-8'), 
						'last_login_datetime' => htmlspecialchars($data['last_login_datetime'], ENT_QUOTES, 'utf-8'), 
						'ip_address' => htmlspecialchars($data['ip_address'], ENT_QUOTES, 'utf-8'), 
						'status' => htmlspecialchars($data['status'], ENT_QUOTES, 'utf-8'), 
						'groups' => $data['groups'], 
						'remember_key' => htmlspecialchars($data['remember_key'], ENT_QUOTES, 'utf-8'), 
						'activate_key' => htmlspecialchars($data['activate_key'], ENT_QUOTES, 'utf-8'), 
						'referal_id' => htmlspecialchars($data['referal_id'], ENT_QUOTES, 'utf-8'), 
						'commission_percent' => htmlspecialchars($data['commission_percent'], ENT_QUOTES, 'utf-8'), 
						'badges' => htmlspecialchars($data['badges'], ENT_QUOTES, 'utf-8')
					);
				}
			}
			
			JO_Session::clear('inserted');
			JO_Session::clear('deleted');
			
			if(isset($db_queries['TRUNCATE'])) {
				$truncate = $db_queries['TRUNCATE'];
				unset($db_queries['TRUNCATE']);
				JO_Session::set('deleted', $truncate);
			}
			
			
			$tmp = array();
			$checked = array();
			foreach($db_queries AS $table => $data) {
				foreach($data AS $key => $res) {
					$key = md5( var_export(array($table, $res), true) );
					if(!isset($checked[$key])) {
						$tmp[] = array(
							'table' => $table,
							'data' => $res
						);
					}
					$checked[$key] = true;
				}
			}
			
			JO_Session::set('inserted', $tmp);
			JO_Session::set('query_error', array());
			
			$this->redirect(WM_Router::create($request->getBaseUrl() . '?module=update&controller=index&action=stepTwo'));
			

		}
    }
    
    public function stepTwoAction() {
   		$db = JO_Db::getDefaultAdapter();
    	$queries = JO_Session::get('deleted');
    	
		
		$this->view->msg_error = array();
		$this->view->msg_success = array();
		
    	
    	$limit = 500;
    	
    	$page = (int)$this->getRequest()->getRequest('page', 0);
		if($page < 0) $page = 0;
    	
    	if($queries && count($queries) >= ( $limit*$page) ) { 
    		
    		for($i = ($limit*$page); $i< ( ($limit*$page) + $limit ); $i++) {
    			if(isset($queries[$i])) {
    				$db->delete($queries[$i]['table'], $queries[$i]['where']);
    			}
    		}
    		$this->refresh(WM_Router::create($this->getRequest()->getBaseUrl() . '?module=update&controller=index&action=stepTwo&page=' . ($page+1)), 1);
    		
    	} else {
    		$this->refresh(WM_Router::create($this->getRequest()->getBaseUrl() . '?module=update&controller=index&action=stepThree'), 3);
    	}
    	
    }
    
    public function stepThreeAction() { 
    	$db = JO_Db::getDefaultAdapter();
		
    	JO_Session::set('deleted', array());
    	
    	$queries = JO_Session::get('inserted');
    	
    	$selected_tables = $this->getRequest()->getParam('tables');
		
		$this->view->msg_error = array();
		$this->view->msg_success = array();
    	
		
    	$limit = 500;
    	
    	$page = (int)$this->getRequest()->getRequest('page', 0);
		if($page < 0) $page = 0;
    	
    	$output = array();
    	for($i=0; $i< min(count($queries), $limit) ; $i++) {
    		$output[] = array_shift($queries);
    	} 
    	
    	JO_Session::set('inserted', $queries);
    	
		$query_error = JO_Session::get('query_error');
		
    	if( $output ) {
    		
    		for($i = 0; $i <= count($output); $i++) { 
    			if(isset($output[$i]) && count($output[$i]['data']) > 0) {
					
					$res = $db->insertIgnore($output[$i]['table'], $output[$i]['data']);
    				$last_id = $db->lastInsertId();
					if(!$last_id && !$res) {
    					$query_error[] = array(
    						'query' => is_array($output[$i]) ? self::generateInsert($output[$i]['table'], $output[$i]['data']) : $output[$i],
    						'last_id' => $last_id . ' ' . $res
    					);
    				}
    				
    			}
    		}
    		if($query_error) {
    			JO_Session::set('query_error', $query_error);
    		}
    		
    		$this->refresh(WM_Router::create($this->getRequest()->getBaseUrl() . '?module=update&controller=index&action=stepThree&page=' . ($page+1)), 2);
    		
    	} else {
    		if($query_error) {
    			$tmp = array();
    			foreach($query_error AS $err) {
    				$tmp[] = "\n============================ ".$err['last_id']." ============================\n";
    				$tmp[] = $err['query'];
    			}
	    		file_put_contents(BASE_PATH . '/cache/error_update.log', implode("\n", $tmp));
    			$this->view->msg_error = count($query_error) . ' Records were not imported. Please check ' . BASE_PATH . '/cache/error_update.log';
    		} else {
    			$fordel = array(
    				BASE_PATH . '/old_sys/',
    				APPLICATION_PATH . '/modules/update/',
    			);
    			$this->view->msg_success = 'All data was successful imported. Your system is updated! Now please delete the following folders: <b>' . implode('</b>; <b>', $fordel) . '<b>';
    			
    		}
    		
    		self::unlink(BASE_PATH . '/old_sys/', true);
    //		self::unlink(APPLICATION_PATH . '/modules/update/', true);
    		JO_Session::set('query_error', array());
    		JO_Session::set('inserted', array());
    	}
    	
	//	$this->refresh(WM_Router::create($this->getRequest()->getBaseUrl() . '?module=themes&controller=index');
    	
    }
    
    //helps
    
    private function generateInsert($table, $data) {
    	$keys = $vals = array();
    	foreach($data AS $k => $v) {
    		$keys[$k] = '`'.$k.'`';
    		$vals[$k] = "'".$v."'";
    	}
    	if(count($keys) == count($vals) && count($keys) > 0) {
    		return "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $vals) . ");";
    	}
    	return '';
    }
    
    private static function copyFile($from, $to) {
    	if($to && is_file(BASE_PATH . '/uploads/' . $to) && file_exists(BASE_PATH . '/uploads/' . $to)) {
    		return $to;
    	} elseif($from && is_file($from)) {
    		$dir = dirname(BASE_PATH . '/uploads/' . $to);
    		if(!file_exists($dir) || !is_dir($dir)) {
    			@mkdir($dir, 0777, true);
    		}
	    	if(@copy($from, BASE_PATH . '/uploads/' . $to)) {
	    		return $to;
	    	}
    	}
    	return '';
    }
    
    private static function is_serialized( $data ) {
	    // if it isn't a string, it isn't serialized
	    if ( !is_string( $data ) )
	        return false;
	    $data = trim( $data );
	    if ( 'N;' == $data )
	        return true;
	    if ( !preg_match( '/^([adObis]):/', $data, $badions ) )
	        return false;
	    switch ( $badions[1] ) {
	        case 'a' :
	        case 'O' :
	        case 's' :
	            if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) )
	                return true;
	            break;
	        case 'b' :
	        case 'i' :
	        case 'd' :
	            if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) )
	                return true;
	            break;
	    }
	    return false;
	}
	
    public static function unlink($dir, $deleteRootToo=true) {
		$dir = rtrim($dir, '/');
	    if(!$dh = @opendir($dir)) {
	        return;
	    }

    	while (false !== ($obj = readdir($dh))) {
	        if($obj == '.' || $obj == '..') {
	            continue;
	        }
	        if (!@unlink($dir . '/' . $obj)) {
	            self::unlink($dir.'/'.$obj, true);
	        }
    	}
    	closedir($dh);
	    if ($deleteRootToo) {
	        @rmdir($dir);
	    }

	    return;
	}
	
	private function recursiveCopy($source, $destination) { 
		$source = rtrim($source, '/');
		$destination = rtrim($destination, '/');
		if(!file_exists($source) || !is_dir($source)) {
			return;
		}
		
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
	
	public static function copyFromArchive($theme_preview) {
		
		if(!file_exists(BASE_PATH . '/uploads/' . $theme_preview) || !is_file(BASE_PATH . '/uploads/' . $theme_preview)) {
			return '';
		}
		
		$zip = new ZipArchive;
		$res = $zip->open(BASE_PATH . '/uploads/' . $theme_preview);

		$upload_path = dirname($theme_preview) . '/';
		$upload_folder = BASE_PATH . '/uploads/' . $upload_path;
		
		$allow_images = array(
			'.jpg',
			'.jpeg',
			'.png',
			'.gif'
		);
		
		$preview = '';
		if($res == true) {
			for($i = 0; $i < $zip->numFiles; $i++) { 
				$file = $zip->getNameIndex($i);
				if(in_array(strtolower(strrchr($file, '.')), $allow_images)) {
					$fileinfo = pathinfo($file);
				    if(isset($fileinfo['basename']) && $fileinfo['basename']) {
						$filename = self::rename_if_exists($upload_folder.'preview/', $fileinfo['basename']);
						@mkdir($upload_folder.'preview/', 0777, true);
						if(copy("zip://".BASE_PATH . '/uploads/' . $theme_preview."#".$file, $upload_folder.'preview/' . $filename)) {
							if(!$preview && file_exists($upload_folder.'preview/' . $filename)) {
								$preview = $upload_path.'preview/' . $filename;
							}
						}
					}
				}
			}
		
			$zip->close();
		}
		
		return $preview;
	}
	
	private function rename_if_exists($dir, $filename) {
    	$dir = rtrim($dir, '/');
	    $ext = strrchr($filename, '.');
	    $prefix = substr($filename, 0, -strlen($ext));
	    $i = 0;
	    while(file_exists($dir . $filename)) { // If file exists, add a number to it.
	        $filename = $prefix . '[' .++$i . ']' . $ext;
	    }
	    return $filename;
	}
    
    

}
