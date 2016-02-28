<?php

class Model_Items {
	
	public static function getItems($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$qqq = new JO_Db_Expr("			(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) AS sum_receive,
			(SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) AS web_profit,
			(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) AS referral_sum,
			( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) ) AS web_profit2,
			(SELECT COUNT(*) FROM items_comments WHERE item_id = i.id LIMIT 1) AS comments
		");
		
		$query = $db->select()
					->from(array('i' => 'items'), array('i.*', $qqq))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'i.user_id = u.user_id', array('username'));
					
		if(isset($data['filter_update']) && $data['filter_update'] === true) {
			$query->joinRight(array('t' => 'temp_items'), 'i.id = t.id', array('name','thumbnail','theme_preview','main_file','main_file_name','reviewer_comment','datetime'));
		}
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
		
		$allow_sort = array(
			'i.id',
			'i.name',
			'u.username',
			'i.price',
			'i.sales',
			'i.earning',
			'i.free_request',
			'i.free_file',
			'web_profit',
			'web_profit2',
			'referral_sum'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('i.id' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('i.id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('i.user_id = ?', (int)$data['filter_user_id']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('i.name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_price']) && $data['filter_price']) {
			$data['filter_price'] = html_entity_decode($data['filter_price'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_price'], '<>') === 0) {
				$query->where('i.price != ?', (float)substr($data['filter_price'], 2));
			} elseif(strpos($data['filter_price'], '>') === 0) {
				$query->where('i.price > ?', (float)substr($data['filter_price'], 1));
			} elseif(strpos($data['filter_price'], '<') === 0) {
				$query->where('i.price < ?', (float)substr($data['filter_price'], 1));
			} else {
				$query->where('i.price = ?', (float)$data['filter_price']);
			}
		}

		if(isset($data['filter_sales']) && $data['filter_sales']) {
			$data['filter_sales'] = html_entity_decode($data['filter_sales'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_sales'], '<>') === 0) {
				$query->where('i.sales != ?', (int)substr($data['filter_sales'], 2));
			} elseif(strpos($data['filter_sales'], '>') === 0) {
				$query->where('i.sales > ?', (int)substr($data['filter_sales'], 1));
			} elseif(strpos($data['filter_sales'], '<') === 0) {
				$query->where('i.sales < ?', (int)substr($data['filter_sales'], 1));
			} else {
				$query->where('i.sales = ?', (int)$data['filter_sales']);
			}
		}
		
		if(isset($data['filter_profit']) && $data['filter_profit']) {
			$data['filter_profit'] = html_entity_decode($data['filter_profit'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_profit'], '<>') === 0) {
				$query->where('i.earning != ?', (float)substr($data['filter_profit'], 2));
			} elseif(strpos($data['filter_profit'], '>') === 0) {
				$query->where('i.earning > ?', (float)substr($data['filter_profit'], 1));
			} elseif(strpos($data['filter_profit'], '<') === 0) {
				$query->where('i.earning < ?', (float)substr($data['filter_profit'], 1));
			} else {
				$query->where('i.earning = ?', (float)$data['filter_profit']);
			}
		}
		
		if(isset($data['filter_web_profit']) && $data['filter_web_profit']) {
			$data['filter_web_profit'] = html_entity_decode($data['filter_web_profit'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_web_profit'], '<>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) != ?', (float)substr($data['filter_web_profit'], 2));
			} elseif(strpos($data['filter_web_profit'], '>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) > ?', (float)substr($data['filter_web_profit'], 1));
			} elseif(strpos($data['filter_web_profit'], '<') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) < ?', (float)substr($data['filter_web_profit'], 1));
			} else {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) = ?', (float)$data['filter_web_profit']);
			}
		}
		
		if(isset($data['filter_refferals']) && $data['filter_refferals']) {
			$data['filter_refferals'] = html_entity_decode($data['filter_refferals'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_refferals'], '<>') === 0) {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) != ?', (float)substr($data['filter_refferals'], 2));
			} elseif(strpos($data['filter_refferals'], '>') === 0) {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) > ?', (float)substr($data['filter_refferals'], 1));
			} elseif(strpos($data['filter_refferals'], '<') === 0) {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) < ?', (float)substr($data['filter_refferals'], 1));
			} else {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) = ?', (float)$data['filter_refferals']);
			}
		}
		
		if(isset($data['filter_free_request']) && in_array($data['filter_free_request'], array('true','false'))) {
			$query->where('i.free_request = ?', $data['filter_free_request']);
		}
		
		if(isset($data['filter_free_file']) && in_array($data['filter_free_file'], array('true','false'))) {
			$query->where('i.free_file = ?', $data['filter_free_file']);
		}
		
		if(isset($data['filter_weekly']) && $data['filter_weekly']) {
			$query->where('? BETWEEN i.weekly_from AND i.weekly_to', JO_Date::getInstance($data['filter_weekly'], 'yy-mm-dd')->toString());
		}
		
		if(isset($data['filter_status']) && $data['filter_status']) {
			$query->where('i.status = ?', $data['filter_status']);
		}
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTempItems($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		$query = $db->select()
					->from(array('i' => 'items'))
					->joinInner(array('ti' => 'temp_items'), 'ti.id = i.id', array('ti.*'))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'i.user_id = u.user_id', array('username'));
		/*			
		if(isset($data['filter_update']) && $data['filter_update'] === true) {
			$query->joinRight(array('t' => 'temp_items'), 'i.id = t.id', array('name','thumbnail','theme_preview','main_file','main_file_name','reviewer_comment','datetime'));
		}
	*/
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
		
		$allow_sort = array(
			'ti.id',
			'ti.name',
			'u.username',
			'ti.free_request',
			'i.free_file'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('i.id' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('ti.id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('i.user_id = ?', (int)$data['filter_user_id']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('ti.name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_free_request']) && in_array($data['filter_free_request'], array('true','false'))) {
			$query->where('ti.free_request = ?', $data['filter_free_request']);
		}
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTotalItems($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$qqq = new JO_Db_Expr("
			COUNT(i.id),
			(SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) AS web_profit,
			(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) AS referral_sum,
			( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = 'buy' AND paid = 'true' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = 'referal' AND paid = 'true' GROUP BY item_id LIMIT 1) ) AS web_profit2
		");
		
		$query = $db->select()
					->from(array('i' => 'items'), $qqq)
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'i.user_id = u.user_id', array());
					
		if(isset($data['filter_update']) && $data['filter_update'] === true) {
			$query->joinRight(array('t' => 'temp_items'), 'i.id = t.id', array('name','thumbnail','theme_preview','main_file','main_file_name','reviewer_comment','datetime'));
		}
		
		////////////filter
		
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('i.id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('i.user_id = ?', (int)$data['filter_user_id']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('i.name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
		if(isset($data['filter_price']) && $data['filter_price']) {
			$data['filter_price'] = html_entity_decode($data['filter_price'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_price'], '<>') === 0) {
				$query->where('i.price != ?', (float)substr($data['filter_price'], 2));
			} elseif(strpos($data['filter_price'], '>') === 0) {
				$query->where('i.price > ?', (float)substr($data['filter_price'], 1));
			} elseif(strpos($data['filter_price'], '<') === 0) {
				$query->where('i.price < ?', (float)substr($data['filter_price'], 1));
			} else {
				$query->where('i.price = ?', (float)$data['filter_price']);
			}
		}
		
		if(isset($data['filter_sales']) && $data['filter_sales']) {
			$data['filter_sales'] = html_entity_decode($data['filter_sales'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_sales'], '<>') === 0) {
				$query->where('i.sales != ?', (int)substr($data['filter_sales'], 2));
			} elseif(strpos($data['filter_sales'], '>') === 0) {
				$query->where('i.sales > ?', (int)substr($data['filter_sales'], 1));
			} elseif(strpos($data['filter_sales'], '<') === 0) {
				$query->where('i.sales < ?', (int)substr($data['filter_sales'], 1));
			} else {
				$query->where('i.sales = ?', (int)$data['filter_sales']);
			}
		}
		
		if(isset($data['filter_profit']) && $data['filter_profit']) {
			$data['filter_profit'] = html_entity_decode($data['filter_profit'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_profit'], '<>') === 0) {
				$query->where('i.earning != ?', (float)substr($data['filter_profit'], 2));
			} elseif(strpos($data['filter_profit'], '>') === 0) {
				$query->where('i.earning > ?', (float)substr($data['filter_profit'], 1));
			} elseif(strpos($data['filter_profit'], '<') === 0) {
				$query->where('i.earning < ?', (float)substr($data['filter_profit'], 1));
			} else {
				$query->where('i.earning = ?', (float)$data['filter_profit']);
			}
		}
		
		if(isset($data['filter_web_profit']) && $data['filter_web_profit']) {
			$data['filter_web_profit'] = html_entity_decode($data['filter_web_profit'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_web_profit'], '<>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) != ?', (float)substr($data['filter_web_profit'], 2));
			} elseif(strpos($data['filter_web_profit'], '>') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) > ?', (float)substr($data['filter_web_profit'], 1));
			} elseif(strpos($data['filter_web_profit'], '<') === 0) {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) < ?', (float)substr($data['filter_web_profit'], 1));
			} else {
				$query->where('( (SELECT SUM(price) - SUM(receive) FROM orders WHERE item_id = i.id AND type = \'buy\' AND paid = \'true\' GROUP BY item_id LIMIT 1) -  (SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) ) = ?', (float)$data['filter_web_profit']);
			}
		}
		
		if(isset($data['filter_refferals']) && $data['filter_refferals']) {
			$data['filter_refferals'] = html_entity_decode($data['filter_refferals'], ENT_QUOTES, 'utf-8');
			if(strpos($data['filter_refferals'], '<>') === 0) {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) != ?', (float)substr($data['filter_refferals'], 2));
			} elseif(strpos($data['filter_refferals'], '>') === 0) {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) > ?', (float)substr($data['filter_refferals'], 1));
			} elseif(strpos($data['filter_refferals'], '<') === 0) {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) < ?', (float)substr($data['filter_refferals'], 1));
			} else {
				$query->where('(SELECT SUM(receive) FROM orders WHERE item_id = i.id AND type = \'referal\' AND paid = \'true\' GROUP BY item_id LIMIT 1) = ?', (float)$data['filter_refferals']);
			}
		}
		
		if(isset($data['filter_free_request']) && in_array($data['filter_free_request'], array('true','false'))) {
			$query->where('i.free_request = ?', $data['filter_free_request']);
		}
		
		if(isset($data['filter_free_file']) && in_array($data['filter_free_file'], array('true','false'))) {
			$query->where('i.free_file = ?', $data['filter_free_file']);
		}
		
		if(isset($data['filter_weekly']) && $data['filter_weekly']) {
			$query->where('? BETWEEN i.weekly_from AND i.weekly_to', JO_Date::getInstance($data['filter_weekly'], 'yy-mm-dd')->toString());
		}
		
		if(isset($data['filter_status']) && $data['filter_status']) {
			$query->where('i.status = ?', $data['filter_status']);
		}
		
		return $db->fetchOne($query);
		
	}
	
	public static function getItem($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(array('i' => 'items'))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'i.user_id = u.user_id', array('username', 'email', 'item_note'))
					->where('i.id = ?', (int)$id);
		
		return $db->fetchRow($query);
		
	}
	
	public static function getItemUpdate($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(array('i' => 'items'))
					->joinRight(array('t' => 'temp_items'), 'i.id = t.id', array('name','thumbnail','theme_preview_thumbnail','theme_preview','main_file','main_file_name','reviewer_comment','datetime'))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'i.user_id = u.user_id', array('username', 'email'))
					->where('i.id = ?', (int)$id);
		
		return $db->fetchRow($query);
		
	}
	
	public static function getItemAttributes($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_attributes')
					->joinLeft('attributes_categories', 'attributes_categories.id = items_attributes.category_id', array('type'))
					->where('items_attributes.item_id = ?', (int)$id);
		
		$result = array();
		$data = $db->fetchAll($query);
		if($data) {
			foreach($data AS $v) {
				if($v['type']) {
					if($v['type'] == 'check') {
						$result[$v['category_id']][$v['attribute_id']] = $v['attribute_id'];
					} elseif(in_array($v['type'], array('select', 'radio', 'input'))) {
						$result[$v['category_id']] = $v['attribute_id'];
					}
				}
			}
		}
		
		return $result;
	}
	
	public static function getItemCollections($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_collections')
					->where('item_id = ?', (int)$id);
		
		$result = array();
		$data = $db->fetchAll($query);
		if($data) {
			foreach($data AS $v) {
				$result[$v['collection_id']] = $v['collection_id'];
			}
		}
					
		return $result;
	}
	
	public static function getItemFaqs($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(array('ic' => 'items_faqs'))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'ic.user_id = users.user_id', array('username'))
					->where('ic.item_id = ?', (int)$id);
					
		return $db->fetchAll($query);
	}
	
	public static function getItemRates($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(array('ic' => 'items_rates'))
					->joinLeft(array('u' => Model_Users::getPrefixDB().'users'), 'ic.user_id = users.user_id', array('username'))
					->where('ic.item_id = ?', (int)$id);
					
		return $db->fetchAll($query);
	}
	
	public static function getItemTags($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_tags')
					->join('tags', 'items_tags.tag_id = tags.id')
					->where('item_id = ?', (int)$id);
					
		$result = '';
		$data = $db->fetchAll($query); 
		if($data) {
			foreach($data AS $v) {
				if(trim($v['name'])) {
					$result = $result . $v['name'] . ',';
				}
			}
		}
					
		return $result;
	}
	
	public static function getItemTagsUpdate($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('temp_items_tags')
					->join('tags', 'temp_items_tags.tag_id = tags.id')
					->where('item_id = ?', (int)$id);
					
		$result = '';
		$data = $db->fetchAll($query); 
		
		if($data) {
			foreach($data AS $v) {
				if(trim($v['name'])) {
					$result[] = $v['name'];
				}
			}
		}
					
		return implode(',', $result);
	}
	
	public static function getItemCategory($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_to_category')
					->where('item_id = ?', (int)$id);
		
		$result = array();
		$data = $db->fetchAll($query);
		if($data) {
			foreach($data AS $cat) {
				$c = explode(',', $cat['categories']);
				foreach($c AS $v) {
					if($v) {
						$result[$v] = $v;
					}
				}
			}
		}
					
		return $result;
	}
	
	public static function changeStatus($id) {
		$item = self::getItem($id);
		$db = JO_Db::getDefaultAdapter();
		if($item['free_file'] == 'false') { 
		//	$db->update('items', array(
		//		'free_file' => 'false'
		//	));
			self::addUserStatus($id, 'freefile');
		}
		$db->update('items', array(
			'free_file' => new JO_Db_Expr("IF(free_file='true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
	public static function deleteItemUpdate($id, $message = '') {
		$db = JO_Db::getDefaultAdapter();
		$info = self::getItemUpdate($id);
		if(!$info) {
			return;
		}
		
		/////////// send email
			
		$request = JO_Request::getInstance();
		$translate = JO_Translate::getInstance();
		
		$not_template = Model_Notificationtemplates::get('queue_update_remove');
		$mail = new JO_Mail;
		if(JO_Registry::get('mail_smtp')) {
			$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
		}
		$mail->setFrom('no-reply@'.$request->getDomain());
		
		if($not_template) {
			$user_info = Model_Users::getUser($info['user_id']);
			$title = $not_template['title'];
			$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
			$html = str_replace('{USERNAME}', $user_info['username'], $html);
			$html = str_replace('{ITEM}', $info['name'], $html);
			$html = str_replace('{MESSAGE}', $message, $html);
		} else {
			$title = "[".$request->getDomain()."] " . $info['name'];
			$html = nl2br($translate->translate('Item is deleted'));
		}
		
		$mail->setSubject($title);
		$mail->setHTML($html);
		$result = $mail->send(array($user_info['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
		unset($mail);
		
		//////////////////////
		
		self::unlink(BASE_PATH . '/uploads/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $id . '/temp/');
		self::unlink(BASE_PATH . '/uploads/cache/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $id . '/');
		
		$db->delete('temp_items', array('id = ?' => (int)$id));
		$db->delete('temp_items_tags', array('item_id = ?' => (int)$id));
		$db->delete('temp_items_attributes', array('item_id = ?' => (int)$id));
		$db->delete('temp_items_to_category', array('item_id = ?' => (int)$id));
	}

	public static function deleteItem($id, $message = '') {
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getItem($id);
		if(!$info) {
			return;
		}
		
		$path = BASE_PATH . '/uploads/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/', true)->toString() . $id . '/';
		self::unlink($path);
		
		$db->delete('items', array('id = ?' => (int)$id));
		$db->delete('items_attributes', array('item_id = ?' => (int)$id));
		$db->delete('items_collections', array('item_id = ?' => (int)$id));
		$db->delete('items_comments', array('item_id = ?' => (int)$id));
		$db->delete('items_faqs', array('item_id = ?' => (int)$id));
		$db->delete('items_rates', array('item_id = ?' => (int)$id));
		$db->delete('items_tags', array('item_id = ?' => (int)$id));
		$db->delete('items_to_category', array('item_id = ?' => (int)$id));
		
		$db->update(Model_Users::getPrefixDB().'users', array(
			'items' => new JO_Db_Expr('items - 1')
		), array('user_id = ?' => $info['user_id']));
		
		/////////// send email
			
		$request = JO_Request::getInstance();
		$translate = JO_Translate::getInstance();
		
		$not_template = Model_Notificationtemplates::get('delete_item');
		$mail = new JO_Mail;
		if(JO_Registry::get('mail_smtp')) {
			$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
		}
		$mail->setFrom('no-reply@'.$request->getDomain());
		
		if($not_template) {
			$user_info = Model_Users::getUser($info['user_id']);
			$title = $not_template['title'];
			$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
			$html = str_replace('{USERNAME}', $user_info['username'], $html);
			$html = str_replace('{ITEM}', $info['name'], $html);
			$html = str_replace('{MESSAGE}', $message, $html);
		} else {
			$title = "[".$request->getDomain()."] " . $info['name'];
			$html = nl2br($translate->translate('Item is deleted'));
		}
		
		$mail->setSubject($title);
		$mail->setHTML($html);
		$result = $mail->send(array($user_info['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
		unset($mail);
		
		//////////////////////
		
		self::deleteItemUpdate($id);
		
		self::unlink(BASE_PATH . '/uploads/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $id . '/');
		self::unlink(BASE_PATH . '/uploads/cache/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $id . '/');
		
		
		
//		$info = self::getItemUpdate($id);
//		if(!$info) {
//			return;
//		}	
//		
//		$db->delete('temp_items', array('item_id = ?' => (int)$id));
//		$db->delete('temp_items_tags', array('item_id = ?' => (int)$id));
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
	
	public static function editItemPartial($item_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		return $db->update('items', $data, array('id=?',(int)$item_id));
	}

}

?>