<?php
class Model_Items {
	
    public function getAll($category_id, $start = 0, $limit = 0, $order = 'id desc', $where = false) {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', array('*', 'IF(items.free_file = true, 0, items.price) AS rprice, (SELECT COUNT(`items_rates`.`item_id`) FROM `items_rates` WHERE `items_rates`.`item_id` = `items`.`id` LIMIT 1) AS downloads'))
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username', 'avatar'))
					->where('items.status = ?', 'active')
					->order($order)
					->group('items.id')
					->limit($limit, $start);
    	
		if($where != false) {
		    $query->where($where);
		}
		
		if(is_numeric($category_id)) {
		    $query->where("id IN (SELECT `item_id` FROM `items_to_category` WHERE `categories` LIKE '%,".intval($category_id).",%') ");
		} 
		//echo $query; exit;
		$return = array();
		
		foreach($db->fetchAll($query) as $d) {  
			$d['categories'] = Model_Categories::getItemsCategories($d['id']);
		
			$categories = explode(',', $d['categories']); 
			$d['categories'] = array();
			
			foreach($categories AS $cat) {
				
				if(!empty($cat) && $cat != '|' && !in_array($cat, array_values($d['categories']))) {
					$d['categories'][] = $cat;
				}
			}
			
			$return[$d['id']] = $d;
		}
		
		return $return; 
	}
	
    public function getAllTags($tags, $start=0, $limit=0, $order='id desc', $where=false) {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items')
					->where('items.status = ?', 'active')

					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username','avatar'))
					->order($order)
					->limit($limit, $start)
					->group('items.id');
    			
		if(is_array($tags)) {
		    $query->where("id IN (SELECT DISTINCT `item_id` FROM `items_tags` WHERE `tag_id` IN (".implode(',',$tags).") ) ");
		} else {
			$query->where("id IN (SELECT DISTINCT `item_id` FROM `items_tags` WHERE `tag_id` = '".(int)$tags."') ");
		}
		
		if($where!=false) {
		    $query->where($where);
		}
		$return = array();
		
        

		foreach($db->fetchAll($query) as $d) {  
		$d['categories'] = Model_Categories::getItemsCategories($d['id']);
		$categories = explode('|', $d['categories']);
			unset($d['categories']);
			$d['categories'] = array();
			$row=0;
			foreach($categories AS $cat) {
				$categories1 = explode(',', $cat);
				foreach($categories1 as $c) {
					$c = trim($c);
					if($c != '') {
						$d['categories'][$row][$c] = $c;
					}
				}
				$row++;
			}
			$return[$d['id']] = $d;
		}
		return $return; 
	}
	
    public function getTotalTags($tags, $where=false) {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', 'COUNT(DISTINCT items.id)')
					->where('items.status = ?', 'active')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', 'username');
    			
		if(is_array($tags)) {
		    $query->where("id IN (SELECT DISTINCT `item_id` FROM `items_tags` WHERE `tag_id` IN (".implode(',',$tags).") ) ");
		} else {
			$query->where("id IN (SELECT DISTINCT `item_id` FROM `items_tags` WHERE `tag_id` = '".(int)$tags."') ");
		}
		
		if($where!=false) {
		    $query->where($where);
		}
		
		return $db->fetchOne($query); 
	}
	
	public function searchItems($collection_id, $start = 0, $limit = 0, $where = false, $search, $order = 'items.id desc') {
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username','avatar'))
					->where('items.status = ?', 'active')
                    ->where(" (MATCH(name,description) AGAINST ('$search')
            			OR
            			name LIKE ('%$search%')
            			OR 
            			description LIKE ('%$search%')
            			)")
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', 'username')
					->order($order)
					->limit($limit, $start)
					->group('items.id');
    			
		/*if(is_numeric($collection_id)) {
		    $query->where("id IN (SELECT `item_id` FROM `items_to_category` WHERE `categories` LIKE '%,".intval($category_id).",%') ");
		}*/		
		
		if($where!=false) {
		    $query->where($where);
		}
		$return = array();
		
         

		foreach($db->fetchAll($query) as $d) {  
		$d['categories'] = Model_Categories::getItemsCategories($d['id']);
		$categories = explode('|', $d['categories']);
			unset($d['categories']);
			$d['categories'] = array();
			$row=0;
			foreach($categories AS $cat) {
				$categories1 = explode(',', $cat);
				foreach($categories1 as $c) {
					$c = trim($c);
					if($c != '') {
						$d['categories'][$row][$c] = $c;
					}
				}
				$row++;
			}
			$return[$d['id']] = $d;
		}
		return $return;
	}
	
	public function countSearchItems($collection_id, $where = false, $search) {
	   
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', new JO_Db_Expr('COUNT(items.id)'))
					->where('items.status = ?', 'active')
                    ->where(" (MATCH(name,description) AGAINST ('$search')
            			OR
            			name LIKE ('%$search%')
            			OR 
            			description LIKE ('%$search%')
            			)");
    			
		/*if(is_numeric($collection_id)) {
		    $query->where("id IN (SELECT `item_id` FROM `items_to_category` WHERE `categories` LIKE '%,".intval($category_id).",%') ");
		}*/		
		
		if($where!=false) {
		    $query->where($where);
		}

		return $db->fetchOne($query);
	}
	
    public function countItems($category_id = 0) {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', new JO_Db_Expr('COUNT(id)'))
					->where('status = ?', 'active');
					
					
		if($category_id!='0') {
		    $query->where("id IN (SELECT `item_id` FROM `items_to_category` WHERE `categories` LIKE '%,".intval($category_id).",%') ");
		}
		
		return $db->fetchOne($query);	
	}
	
	public function countByUser($user) {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', new JO_Db_Expr('COUNT(id)'))
					->where('status = ?', 'active')
					->where('user_id = ?', $user);
					
					
		
		return $db->fetchOne($query);	
	}
	
	public function get($item_id, $active = FALSE) {
		$db = JO_Db::getDefaultAdapter();
	    $percents = 0;
		if(JO_Registry::get('prepaid_price_discount')) {
			$percents = JO_Registry::get('prepaid_price_discount');
		}
		
		$extended_price = 1;
		if(JO_Registry::get('extended_price')) {
			$extended_price = (int)JO_Registry::get('extended_price');
		}
 
		$query = $db->select()
					->from('items')
					->where('items.id = ?', (int)$item_id);
					
					if($active==TRUE)
					    $query->where('status = ?', 'active');
		
		$return = $db->fetchRow($query);

		if(!$return) {
			return false;
		}
		
		if(strpos($percents, '%') !== false) {
			$return['prepaid_price'] = $return['price'] - ( ( $return['price'] / 100 ) * (int)$percents );
			$return['your_profit'] = (float)( ( $return['price'] / 100 ) * (int)$percents );
		} else {
			$return['prepaid_price'] = $return['price'] - (int)$percents;
			$return['your_profit'] = (float)$percents;
		}
		
		$return['extended_price'] = $return['price'] * $extended_price;
		
		
		$query = $db->select()
					->from('items_to_category')
					->where('items_to_category.item_id = ?', (int)$item_id);
		
		$cats = $db->fetchAll($query);
		//$cats = 0;
		$return['categories'] = array();
		if($cats > 0) {
			$row=0;
			foreach($cats as $ca) {
				$categories = explode(',', $ca['categories']);
				foreach($categories as $c) {
					$c = trim($c);
					if($c != '') {
						$return['categories'][$row][$c] = $c;
					}
				}
				$row++;
			}
		}
		
		
		$query = $db->select()
					->from('items_tags')
					->joinLeft('tags', 'tags.id = items_tags.tag_id')
					->where('items_tags.item_id = ?', (int)$item_id)
					->where('tags.visible =? ', 'true');
		
		$tags = $db->fetchAll($query);

		if(count($tags) > 0) {
			foreach($tags as $d) {
				$return['tags'][$d['tag_id']] = $d['name'];
			}
		}
		
		$query = $db->select()
					->from('items_attributes')
					->where('items_attributes.item_id = ?', (int)$item_id);
		
		$attributes = $db->fetchAll($query);
		
		$return['this']['attributesCategoriesWhere'] = false;
		$return['this']['attributesWhere'] = false;
	if(count($attributes) > 0) {
			foreach($attributes as $d) {
				if(isset($return['attributes'][$d['category_id']])) {
					if(!is_array($return['attributes'][$d['category_id']])) {
						$val = $return['attributes'][$d['category_id']];
						unset($return['attributes'][$d['category_id']]);
						$return['attributes'][$d['category_id']][$val] = $val;
					}
					$return['attributes'][$d['category_id']][$d['attribute_id']] = $d['attribute_id'];

					if($return['this']['attributesWhere'] != '') {
						$return['this']['attributesWhere'] .= " OR ";
					}
					$return['this']['attributesWhere'] .= " `id` = '".intval($d['attribute_id'])."' ";
				}
				else {
					$return['attributes'][$d['category_id']] = $d['attribute_id'];
					
					if($return['this']['attributesCategoriesWhere'] != '') {
						$return['this']['attributesCategoriesWhere'] .= " OR ";
					}
					$return['this']['attributesCategoriesWhere'] .= " `id` = '".intval($d['category_id'])."' ";

					if($return['this']['attributesWhere'] != '') {
						$return['this']['attributesWhere'] .= " OR ";
					}
					$return['this']['attributesWhere'] .= " `id` = '".intval($d['attribute_id'])."' ";
				}
			}
	}

		
		return $return;


		
		
					
	}
	
    public function getWeekly($filter = null) {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username', 'avatar'))
					->where('items.status = ?', 'active')
					->where('items.weekly_to >= ?', date('Y-m-d'))
					->order('items.datetime DESC')
					;
		
		if(!is_null($filter)) {
			$query->joinLeft(Model_Users::getPrefixDB().'items_to_category', 'items_to_category.item_id = items.id');
			$query->group('items.id');
			
			$return = '';
			$items = $db->fetchAll($query);
			
			if($items) {
				$cnt = 0;
				foreach($items as $item) {
					$cats = explode(',', $item['categories']);
					if($cats[1] == $filter) {
						$return[] = $item; 
						$cnt++;
						
						if($cnt >= 28) break;
					}
				}
			}
			
			return $return;
		} else {
			$query->limit(28, 0);
			
			return $db->fetchAll($query);
		}	
	}
	
    public function countWeekly() {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', new JO_Db_expr('COUNT(`id`)'))
					->where('status = ?', 'active')
					->where('weekly_to >= ?', date('Y-m-d'));
    	return $db->fetchOne($query);
	}
	
    public function getRecent($filter = null) {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username', 'avatar'))
					->where('items.status = ?', 'active')
					->order('items.datetime DESC')
					;
		
		if(!is_null($filter)) {
			$query->joinLeft(Model_Users::getPrefixDB().'items_to_category', 'items_to_category.item_id = items.id');
			$query->group('items.id');
			
			$return = '';
			$items = $db->fetchAll($query);
			
			if($items) {
				$cnt = 0;
				foreach($items as $item) {
					$cats = explode(',', $item['categories']);
					if($cats[1] == $filter) {
						$return[] = $item; 
						$cnt++;
						
						if($cnt >= 28) break;
					}
				}
			}
			
			return $return;
			
		} else {
			$query->limit(28, 0);
			
			return $db->fetchAll($query);
		}			
	}
	
    public function countFollowers($id) {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('users_followers', new JO_Db_expr('COUNT(`items`.`id`)'))
					->joinLeft('items', 'items.user_id = users_followers.follow_id', '*')
					->where('users_followers.user_id = ?', (int)$id);
					
    	return $db->fetchOne($query);
	}
	
	
	public function getFollowers($id, $start=0) {

    $db = JO_Db::getDefaultAdapter();
	$query = $db->select()
					->from('users_followers')
					->joinLeft('items', 'items.user_id = users_followers.follow_id', '*')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username', 'avatar'))
					->where('users_followers.user_id = ?', (int)$id)
					->group('items.id')
					->limit(9, $start);
					
	    return $db->fetchAll($query);
	}

    public function getFreeFiles() {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items')
				//	->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username', 'avatar'))
					->joinLeft(Model_Users::getPrefixDB().'items_to_category', 'items_to_category.item_id = items.id', array('categories'))
					->where('items.status = ?', 'active')
					->where('items.free_file = ?', 'true')
					->group('items.id')
					->order('rand()')
				//	->order('items.datetime DESC')
				//	->limit(1, 0)
					;
	
		return $db->fetchAll($query);	
	}
	
	public function getByUser($user_id, $start=0, $limit=0, $order='`items`.datetime desc', $where='') {
     
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', array('*', 'IF(items.free_file = true, 0, items.price) AS rprice, (SELECT COUNT(`items_rates`.`item_id`) FROM `items_rates` WHERE `items_rates`.`item_id` = `items`.`id` LIMIT 1) AS downloads'))
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username', 'avatar'))
					->where('items.status = ?', 'active')
					->where('items.user_id = ?', (int)$user_id)
					->limit($limit, $start);
					
					if($order)
						$query->order($order);
					
					if($where) 
						$query->where($where);
		
		//echo $query; exit;
		$return = array();
		
		foreach($db->fetchAll($query) as $d) {  
			$d['categories'] = Model_Categories::getItemsCategories($d['id']);
		
			$categories = explode(',', $d['categories']); 
			$d['categories'] = array();
			
			foreach($categories AS $cat) {
				
				if(!empty($cat) && $cat != '|' && !in_array($cat, array_values($d['categories']))) {
					$d['categories'][] = $cat;
				}
			}
			
			$return[$d['id']] = $d;
		}
		
		return $return;
	}
	
	///////////// by joro
	
	public static function isRate($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('items_rates')
					->where('item_id = ?', (int)$id)
					->where('user_id = ?', JO_Session::get('user_id'));
					
		return $db->fetchRow($query);
	}
	
	public static function rate($id, $rate) {
		$db = JO_Db::getDefaultAdapter();
		
		$item = self::get($id);
		$row = self::isRate($id);

		if($row) {
			return $item;
		}
		
		$item['votes'] = $item['votes'] + 1;
		$item['score'] = $item['score'] + $rate;
		$item['rating'] = $item['score'] / $item['votes'];
		$item['rating'] = round($item['rating']);
		
		$db->update('items', array(
			'rating' => (int)$item['rating'],
			'score' => (int)$item['score'],
			'votes' => (int)$item['votes']
		), array('id = ?' => (int)$id));
		
		$db->insert('items_rates', array(
			'item_id' => (int)$id,
			'user_id' => (int)JO_Session::get('user_id'),
			'rate' => (int)$rate,
			'datetime' => new JO_Db_Expr('NOW()')
		));
		
#INC USER RATES
		$user = Model_Users::getUser(JO_Session::get('user_id'));
		
		$user['votes'] = $user['votes'] + 1;
		$user['score'] = $user['score'] + $rate;
		$user['rating'] = $user['score'] / $user['votes'];
		$user['rating'] = round($user['rating']);
		
		$db->update(Model_Users::getPrefixDB().'users', array(
			'rating' => (int)$user['rating'],
			'score' => (int)$user['score'],
			'votes' => (int)$user['votes']
		), array('user_id = ?' => (int)JO_Session::get('user_id')));
		
		return $item;
	}
	
 public function getTopSellers($start=0, $limit=0, $where=false, $order = 'items.sales DESC') {

        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders', new JO_Db_Expr('* , COUNT( `item_id` ) AS `sales` '))
					->joinLeft(Model_Users::getPrefixDB().'items', 'items.id = orders.item_id')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('users.username','users.avatar'))
					->where('items.status = ?', 'active')
					->where('orders.type = ?', 'buy')
					->where('orders.paid = ?', 'true')
					->order($order)
					->limit($limit, $start)
					->group('orders.item_id');
		
		if($where!=false) {
		    $query->where($where);
		}
		//echo $query;
		$return = array();
		
		foreach($db->fetchAll($query) as $d) {  
			$d['categories'] = Model_Categories::getItemsCategories($d['id']);
		
			$categories = explode(',', $d['categories']); 
			$d['categories'] = array();
			
			foreach($categories AS $cat) {
				
				if(!empty($cat) && $cat != '|' && !in_array($cat, array_values($d['categories']))) {
					$d['categories'][] = $cat;
				}
			}
			
			$return[$d['id']] = $d;
		}
		
		return $return;
	}
	
	public function getTopSellersCount($where=false) {

        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('orders', new JO_Db_Expr('* , COUNT( `item_id` ) AS `sales` '))
					->joinLeft(Model_Users::getPrefixDB().'items', 'items.id = orders.item_id')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('users.username','users.avatar'))
					->where('items.status = ?', 'active')
					->where('orders.type = ?', 'buy')
					->where('orders.paid = ?', 'true')
					->where($where)
					->group('orders.item_id');
		
		return count($db->fetchAll($query));
	}
	
	public function getPopularFilesDates() 
	{
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('orders', new JO_Db_Expr('DATE_FORMAT(orders.paid_datetime, \'%m-%Y\') AS paid_date'))
					->join(Model_Users::getPrefixDB().'items', 'items.id = orders.item_id', '')
					->where('items.status = ?', 'active')
					->where('orders.type = ?', 'buy')
					->where('orders.paid = ?', 'true')
					->where('YEAR(NOW()) - 1 <= YEAR(orders.paid_datetime)')
					->group('paid_date')
					->order('DATE_FORMAT(orders.paid_datetime, \'%Y%m\')  DESC');
		//echo $query;		
		return $db->fetchAll($query);
	}
	
 public function getAllByCollection($collection_id, $start=0, $limit=0, $order='items.id desc', $where=false, $search = false) {

        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items')
					->where('items.status = ?', 'active')
                    ->joinLeft('items_collections', 'items_collections.item_id = items.id')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', array('username', 'avatar'))
					->order($order)
					->limit($limit, $start)
					->where('items_collections.collection_id = ?', $collection_id)
					->group('items.id');
    			
		
		if($where!=false) {
		    $query->where($where);
		}
		
		if($search) 
		    $query->where("MATCH(items.name, items.description) AGAINST ('$search')
            			OR
            			items.name LIKE ('%$search%')
            			OR 
            			items.description LIKE ('%$search%')
            			");
		    
		//echo $query;
		$return = array();
		
		foreach($db->fetchAll($query) as $d) {  
			$d['categories'] = Model_Categories::getItemsCategories($d['id']);
		
			$categories = explode(',', $d['categories']); 
			$d['categories'] = array();
			
			foreach($categories AS $cat) {
				
				if(!empty($cat) && $cat != '|' && !in_array($cat, array_values($d['categories']))) {
					$d['categories'][] = $cat;
				}
			}
			
			$return[$d['id']] = $d;
		}

		return $return; 
	}
	
public function CountByCollection($collection_id, $where=false, $search = false) {

        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', new JO_Db_Expr('COUNT(items.id)'))
					->where('items.status = ?', 'active')
                    ->joinLeft('items_collections', 'items_collections.item_id = items.id')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = items.user_id', 'users.username')
					->where('items_collections.collection_id = ?', $collection_id);
    			
		
		if($where!=false) {
		    $query->where($where);
		}
		if($search) 
		    $query->where("MATCH(items.name, items.description) AGAINST ('$search')
            			OR
            			items.name LIKE ('%$search%')
            			OR 
            			items.description LIKE ('%$search%')
            			");
		    

		return $db->fetchOne($query);
	} 
	
    public function countItemsByCollection($collection_id = 0) {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items', new JO_Db_Expr('COUNT(id)'))
					->joinLeft('items_collections', 'items_collections.item_id = items.id')
					->where('items_collections.collection_id = ?', $collection_id)
					->where('status = ?', 'active');
		
		
		return $db->fetchOne($query);	
	}
	
	public static function getAll2() {
		
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items')
					->where('status = ?', 'active')
					->order('id DESC');
    	
		return $db->fetchAll($query); 
	}
	
	public function add($data) {
	    
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->insert('items', array(
	        'user_id'	        =>    $data['user_id'],
	        'name'			    =>    $data['name'],
	        'description'	    =>    $data['description'],
	        'demo_url'	        =>    $data['demo_url'],
	        'reviewer_comment'	=>    $data['reviewer_comment'],
	        'datetime'	   	    =>    new JO_Db_Expr('NOW()'),
	        'status'			=>    'queue',
	        'suggested_price'	=>    (float)$data['suggested_price'],
	        'free_request'	    =>    $data['free_request'],
	    	'module'			=>	  $data['default_module']
	    ));
	    return $db->lastInsertId();
	}
	
	public function updatePics($data) {
	     $db = JO_Db::getDefaultAdapter();
	    
	    $db->update('items', array(
	                    'thumbnail'	=>    $data['thumbnail'],
            		    'theme_preview_thumbnail'	=>    $data['theme_preview_thumbnail'],
            		    'theme_preview'	=>    $data['theme_preview'],
            		    'main_file'		=>    $data['main_file'],
            		    'main_file_name'	=>   $data['main_file_name'] 
	    ),
	    array('id =?'=>$data['id']));
	}
	
	public function updateTempPics($data) {
	    $db = JO_Db::getDefaultAdapter();
	    
		$update_array = array();
		 !empty($data['thumbnail']) && $update_array['thumbnail'] = $data['thumbnail'];
		 !empty($data['theme_preview_thumbnail']) && $update_array['theme_preview_thumbnail'] = $data['theme_preview_thumbnail'];
		 !empty($data['theme_preview']) && $update_array['theme_preview'] = $data['theme_preview'];
		 
		 if(!empty($data['main_file'])) {
		 	$update_array['main_file'] = $data['main_file'];
			$update_array['main_file_name'] = $data['main_file_name'];
		 }
		
	    $db->update('temp_items', $update_array,
	    array('id =?'=>$data['id']));
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
	
    public function isInUpdateQueue($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('temp_items', new JO_Db_Expr('COUNT(item_id)'))
					->where('item_id = ?', (int)$id);
	    $res = $db->fetchOne($query);
        return $res == 0 ? false : true; 
		}
		
    public function updateItem($data) {
	     $db = JO_Db::getDefaultAdapter();
	    
		$query = 'SELECT * FROM temp_items WHERE id = \''. (int)$data['id'] .'\'';
		
		$result = $db->fetchRow($query);
		
		if($result) {
		    $db->update('temp_items', array(
		    			'name'			=>		  $data['name'],
	        		    'description'	=>        $data['description'],
	        		    'demo_url'		=>        $data['demo_url'],
	        		    'free_request'	=>        $data['free_request'],
	        		    'reviewer_comment'	=>    $data['reviewer_comment'],
	        		    'datetime'			=>    new JO_Db_Expr('NOW()'),
	        		    'suggested_price'	=>	  $data['suggested_price'],
	        		    'module'		=>        $data['default_module']
		    ),
		    array('id =?'=>(int)$data['id']));
		} else {
			$db->insert('temp_items', array(
						'id'			=>		  $data['id'],
						'name'			=>		  $data['name'],
	        		    'description'	=>        $data['description'],
	        		    'demo_url'		=>        $data['demo_url'],
	        		    'free_request'	=>        $data['free_request'],
	        		    'reviewer_comment'	=>    $data['reviewer_comment'],
	        		    'datetime'			=>    new JO_Db_Expr('NOW()'),
	        		    'suggested_price'	=>	  $data['suggested_price'],
	        		    'module'		=>        $data['default_module']
		    ));
		}
	}

	public function delete($item_id) {
	    $db = JO_Db::getDefaultAdapter();
	    
	    $info = self::get($item_id);
		if(!$info) {
			return;
		}

	    $db->delete('items', array('id=?'=>$item_id));
	    $db->delete('temp_items', array('id=?'=>$item_id));
	    $db->delete('temp_items_tags', array('item_id=?'=>$item_id));
	    $db->delete('items_attributes', array('item_id=?'=>$item_id));
	    $db->delete('items_collections', array('item_id=?'=>$item_id));
	    $db->delete('items_comments', array('item_id=?'=>$item_id));
	    $db->delete('items_faqs', array('item_id=?'=>$item_id));
	    $db->delete('items_rates', array('item_id=?'=>$item_id));
	    $db->delete('items_tags', array('item_id=?'=>$item_id));
	    $db->delete('items_to_category', array('item_id=?'=>$item_id));
	    $db->update(Model_Users::getPrefixDB().'users', array(
			'items' => new JO_Db_Expr('items - 1')
		), array('user_id = ?' => $info['user_id']));

		self::unlink(BASE_PATH . '/uploads/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $item_id . '/');
        self::unlink(BASE_PATH . '/uploads/cache/items/' . JO_Date::getInstance($info['datetime'], 'yy/mm/',true)->toString() . $item_id . '/');
	    	
	}
	
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
	
	public function getLastItem() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('items')
					->where('items.status = ?', 'active')
					->order('datetime DESC')
					->limit(1, 0);
					
					
		return $db->fetchRow($query);
	}

	public static function getPortfolioCounts($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->union(array('SELECT COUNT(`user_id`) AS total, "collections_cnt" AS "type" FROM `collections` WHERE `collections`.`user_id` = '. $user_id . (JO_Session::get('user_id') == $user_id ? '' : ' AND public = \'true\''),
						'SELECT COUNT(`user_id`) AS total, "items_cnt" AS "type" FROM `items` WHERE `items`.`user_id` = '. $user_id . (JO_Session::get('user_id') == $user_id ? '' : ' AND `items`.`status` = \'active\''),
						'SELECT COUNT(`user_id`) AS total, "following_cnt" AS "type" FROM `users_followers` WHERE `users_followers`.`follow_id` = '. $user_id,
						'SELECT COUNT(`user_id`) AS total, "followers_cnt" AS "type" FROM `users_followers` WHERE `users_followers`.`user_id` = '. $user_id,
						'SELECT COUNT(*) AS total, "downloads_cnt" AS "type" FROM (SELECT DISTINCT item_id FROM `orders` WHERE `orders`.`user_id` = '. $user_id .' AND `orders`.paid = \'true\') o'))
					->order('type');
			
		return $db->fetchAll($query);
	}
	
	public static function getSocials() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
			->from('socials')
			->where('visible = ?', 'true')
			->order('order_index');
			
		return $db->fetchAll($query);
	}
	
	public static function getAttributes($item_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = "SELECT ia.attribute_id, a.name, a.photo, a.search, ac.name AS category
				FROM items_attributes ia 
				JOIN attributes_categories ac ON ac.id = ia.category_id AND ac.visible='true'
				LEFT JOIN attributes a ON a.id = ia.attribute_id AND a.visible='true'
				WHERE ia.item_id = '". (int)$item_id ."'
				ORDER BY ac.order_index, a.order_index ASC";
		
		return $db->fetchAll($query);
	}
}