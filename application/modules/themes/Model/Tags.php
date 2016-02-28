<?php

class Model_Tags {
	
	public static function getTagByTitle($tag) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('tags')
					->where('name LIKE ?', '%'.$tag.'%')
					->where('visible =?', 'true');
					

		$result = $db->fetchRow($query);
		if($result) {
			return $result['id'];
		}
		return false;
	}
	
public static function getTagsByTitle($tag) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('tags')
					->where('name LIKE ?', '%'.$tag.'%')
					->where('visible = ?', 'true');
		return $db->fetchAll($query);
	}
	
	public function addToItem($item_id, $data) {
		$db = JO_Db::getDefaultAdapter();
	  
	  	$data = array_filter($data, create_function('$a','return trim($a)!="";'));
		
		$all_tags = $db->fetchAll("SELECT name FROM tags");
		$all_tag_names = JO_Array::multi_array_to_single_uniq($all_tags);
		
		$diff = array_diff($data, $all_tag_names);
		
		if($diff) {
			$query = 'INSERT INTO `tags` (name) 
					VALUES (\''. implode('\'), (\'', $diff) .'\')';
			$db->query($query);
		}	
		
		$query = 'SELECT id FROM `tags` WHERE name IN (\''. implode('\',\'', $data) .'\')';
		$tags = $db->fetchAll($query);
		
		$query = 'INSERT INTO `items_tags` (item_id, tag_id) VALUES (\'';
		
		$values = JO_Array::multi_array_to_single_uniq($tags);
		$vals = array();
		foreach($values as $val) {
			$vals[] = (int)$item_id .'\', \''. (int)$val;
		}
		
		$query .= implode('\'), (\'', $vals) .'\')';
		
		$db->query($query);
	}
	
	public function updateToItem($item_id, $data) {
		$db = JO_Db::getDefaultAdapter();
	  
	  	$data = array_filter($data, create_function('$a','return trim($a)!="";'));
		
		$all_tags = $db->fetchAll("SELECT name FROM tags");
		$all_tag_names = JO_Array::multi_array_to_single_uniq($all_tags);
		
		$diff = array_diff($data, $all_tag_names);
		
		if($diff) {
			$query = 'INSERT INTO `tags` (name) 
					VALUES (\''. implode('\'), (\'', $diff) .'\')';
			$db->query($query);
		}	
		
		$query = 'SELECT id FROM `tags` WHERE name IN (\''. implode('\',\'', $data) .'\')';
		$tags = $db->fetchAll($query);
		
		$query = 'INSERT INTO `temp_items_tags` (item_id, tag_id) VALUES (\'';
		
		$values = JO_Array::multi_array_to_single_uniq($tags);
		$vals = array();
		foreach($values as $val) {
			$vals[] = (int)$item_id .'\', \''. (int)$val;
		}
		
		$query .= implode('\'), (\'', $vals) .'\')';
		
		$db->query($query);
	}
	
    public function deleteItem($item_id) {
         $db = JO_Db::getDefaultAdapter();
	    $db->delete('items_tags', array(
	        'item_id = ?'	=>    $item_id,
	    ));
    }
	 
	public function deleteTempToItem($item_id) {
         $db = JO_Db::getDefaultAdapter();
	    $db->delete('temp_items_tags', array(
	        'item_id = ?'	=>    $item_id,
	    ));
    }
	
	public function addTag($tag, $visible) {
	$db = JO_Db::getDefaultAdapter();
	    $db->insert('tags', array(
	        'name'	=>    $tag,
	        'visible'	=>    $visible
	    ));
	    return $db->lastInsertId();
	}
	
	public static function getTagByTitleAndInsert($tag) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('tags')
					->where('name LIKE ?', $tag);
		
		$result = $db->fetchRow($query);
		if($result) {
			return $result['id'];
		} else {
			$db->insert('tags', array(
				'name' => $tag,
				'visible' => 'false'
			));
			return $db->lastInsertId();
		}
		return false;
	}
	
	public static function getAllSearchItems($tag, $order, $category_id = false) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT i.*, IF(i.free_file = true, 0, i.price) AS rprice,
					c.id AS category_id, cd.name AS category_name, ic1.categories, u.username 
				FROM tags t
				JOIN items_tags it ON it.tag_id = t.id
				JOIN items i ON i.id = it.item_id
				JOIN items_to_category ic ON ic.item_id = i.id
				JOIN users u ON u.user_id = i.user_id
				JOIN categories c ON FIND_IN_SET(c.id, ic.categories) '. ($category_id ? ' AND c.id = \''. $category_id .'\'' : '') .' 
					AND c.visible = \'true\'
				JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
				JOIN (SELECT item_id, GROUP_CONCAT(categories) AS categories
					FROM items_to_category
					GROUP BY item_id) ic1 ON ic1.item_id = i.id
				WHERE t.name = '. $db->quote($tag) .'
					AND t.visible = \'true\'
				GROUP BY i.id
				ORDER BY '. $order;
		
		return $db->fetchAll($query);
	}
	
	public static function getCategories($tag, $category_id = false) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT (SELECT GROUP_CONCAT(CONCAT(c.id,\'@@@\',cd.name) SEPARATOR \'|||\') 
					FROM categories c
					JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
					WHERE FIND_IN_SET(c.id,ic.categories) 
					LIMIT 1) AS categories 
				FROM tags t
				JOIN items_tags it ON it.tag_id = t.id
				JOIN items i ON i.id = it.item_id
				JOIN items_to_category ic ON ic.item_id = i.id
				JOIN categories c ON FIND_IN_SET(c.id, ic.categories) '. ($category_id ? ' AND c.id = \''. $category_id .'\'' : '') .' 
					AND c.visible = \'true\'
				JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
				WHERE t.name = '. $db->quote($tag) .'
					AND t.visible = \'true\'
				GROUP BY categories';
	
		return $db->fetchAll($query);
	}
}

?>