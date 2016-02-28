<?php
class Model_Search {
	
	public static function getAllSearchItems($keyword, $order, $category_id = false) {
		$db = JO_Db::getDefaultAdapter();
		
		$keyword_parts = explode(' ', mb_strtolower($keyword, 'UTF-8'));
		$cnt = count($keyword_parts);
		
		if($cnt == 1) {
			$query = 'SELECT i.*, IF(i.free_file = true, 0, i.price) AS rprice, @v := if(@v, @v + 1, 1) AS relevance,
					c.id AS category_id, cd.name AS category_name, ic1.categories, u.username
				FROM items i
				JOIN users u ON u.user_id = i.user_id
				JOIN items_to_category ic ON ic.item_id = i.id
				JOIN categories c ON FIND_IN_SET(c.id, ic.categories) '. ($category_id ? ' AND c.id = '. $db->quote($category_id) : '') .' 
					AND c.visible = \'true\'
				JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
				JOIN (SELECT item_id, GROUP_CONCAT(categories) AS categories
					FROM items_to_category
					GROUP BY item_id) ic1 ON ic1.item_id = i.id
				JOIN (SELECT @v := 0) AS r
				WHERE (i.name LIKE '. $db->quote('%'.$keyword.'%') .' OR i.description LIKE '. $db->quote('%'.$keyword.'%') .')
					AND i.status = \'active\'
				GROUP BY i.id';
		
		} else {
			$query = 'SELECT t1.*, IF(t1.free_file = true, 0, t1.price) AS rprice, @v := if(@v, @v + 1, 1) AS relevance,
						c.id AS category_id, cd.name AS category_name, ic1.categories, u.username
					FROM ( 
						SELECT * FROM items
						WHERE (name LIKE '. $db->quote('%'.$keyword.'%') .' OR description LIKE '. $db->quote('%'.$keyword.'%') .')
							AND status = \'active\'
						UNION 
						SELECT * FROM items
						WHERE ((name LIKE '. $db->quote('%'. str_replace(' ', '%', $keyword) .'%') .' AND name NOT LIKE '. $db->quote('%'.$keyword.'%') .')
							OR (description LIKE '. $db->quote('%'. str_replace(' ', '%', $keyword) .'%') .' AND description NOT LIKE '. $db->quote('%'.$keyword.'%') .'))
							AND status =\'active\'
					) AS t1
					JOIN items_to_category ic ON ic.item_id = t1.id
					JOIN users u ON u.user_id = t1.user_id
					JOIN categories c ON FIND_IN_SET(c.id, ic.categories) '. ($category_id ? ' AND c.id = '. $db->quote($category_id) : '') .' 
						AND c.visible = \'true\'
					JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
					JOIN (SELECT item_id, GROUP_CONCAT(categories) AS categories
						FROM items_to_category
						GROUP BY item_id) ic1 ON ic1.item_id = t1.id
					JOIN (SELECT @v := 0) AS r
					GROUP BY t1.id';
		}
		
		$query .= ' ORDER BY '. $order;
		
		return $db->fetchAll($query);
	}

	public static function getCategories($keyword, $category_id = false) {
		$db = JO_Db::getDefaultAdapter();
		
		$keyword_parts = explode(' ', mb_strtolower($keyword, 'UTF-8'));
		$cnt = count($keyword_parts);
		
		if($cnt == 1) {
			$query = 'SELECT (SELECT GROUP_CONCAT(CONCAT(id,\'@@@\',name) SEPARATOR \'|||\') 
						FROM categories_description
						WHERE FIND_IN_SET(id,ic.categories) AND lid = \''. JO_Session::get('language_id') .'\'
						LIMIT 1) AS categories 
					FROM items i 
					JOIN items_to_category ic ON ic.item_id = i.id 
					WHERE (i.name LIKE '. $db->quote('%'.$keyword.'%') .' OR i.description LIKE '. $db->quote('%'.$keyword.'%') .') 
						'. ($category_id ? 'AND ic.categories LIKE '. $db->quote('%'.$category_id.'%') : '') .'
						AND i.status = \'active\'';
		
		} else {
			$query = 'SELECT (SELECT GROUP_CONCAT(CONCAT(id,\'@@@\',name) SEPARATOR \'|||\')
						FROM categories_description
						WHERE FIND_IN_SET(id,ic.categories) AND lid = \''. JO_Session::get('language_id') .'\'
						LIMIT 1) AS categories 
					FROM ( 
						SELECT * FROM items
						WHERE (name LIKE '. $db->quote('%'.$keyword.'%') .' OR description LIKE '. $db->quote('%'.$keyword.'%') .')
							AND status = \'active\'
						UNION 
						SELECT * FROM items
						WHERE ((name LIKE '. $db->quote('%'. str_replace(' ', '%', $keyword) .'%') .' AND name NOT LIKE '. $db->quote('%'.$keyword.'%') .')
							OR (description LIKE '. $db->quote('%'. str_replace(' ', '%', $keyword) .'%') .' AND description NOT LIKE '. $db->quote('%'.$keyword.'%') .'))
							AND status =\'active\'
					) AS t1
					JOIN items_to_category ic ON ic.item_id = t1.id
					'. ($category_id ? 'AND ic.categories LIKE '. $db->quote('%'.$category_id.'%') : '');
		}
		
		$query .= ' GROUP BY categories';
		
		return $db->fetchAll($query);
	}
	
	public static function getAllSearchItemsByAttr($attributes, $order, $category_id = false) {
		$db = JO_Db::getDefaultAdapter();
		
		$attributes = array_map('strtolower', $attributes);
		
		$query = 'SELECT i.*, IF(i.free_file = true, 0, i.price) AS rprice, c.id AS category_id, 
					cd.name AS category_name, ic1.categories, u.username 
				FROM attributes_categories ac
				JOIN attributes a ON a.category_id = ac.id AND LOWER(a.name) = '. $db->quote(mb_strtolower($attributes[1], 'UTF-8')) .' AND a.visible = \'true\'
				JOIN items_attributes ia ON ia.category_id = ac.id AND ia.attribute_id = a.id
				JOIN items i ON i.id = ia.item_id AND i.status = \'active\'
				JOIN users u ON u.user_id = i.user_id
				JOIN items_to_category ic ON ic.item_id = i.id 
				JOIN categories c ON FIND_IN_SET(c.id, ic.categories) '. ($category_id ? ' AND c.id = '. $db->quote($category_id) : '') .' 
					AND c.visible = \'true\' 
				JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
				JOIN (SELECT item_id, GROUP_CONCAT(categories) AS categories 
					FROM items_to_category 
					GROUP BY item_id) ic1 ON ic1.item_id = i.id 
				WHERE LOWER(ac.name) = '. $db->quote(mb_strtolower($attributes[0], 'UTF-8')) .' 
					AND ac.visible = \'true\'
				GROUP BY i.id';
		
		return $db->fetchAll($query);
	}
	
	public static function getCategoriesByAttr($attributes, $category_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$attributes = array_map('strtolower', $attributes);
		
		$query = 'SELECT (SELECT GROUP_CONCAT(CONCAT(c.id,\'@@@\', cd.name) SEPARATOR \'|||\') 
						FROM categories c 
						JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
						WHERE FIND_IN_SET(c.id,ic.categories)
						LIMIT 1) AS categories 
					FROM attributes_categories ac
					JOIN attributes a ON a.category_id = ac.id AND LOWER(a.name) = '. $db->quote(mb_strtolower($attributes[1], 'UTF-8')) .' AND a.visible = \'true\'
					JOIN items_attributes ia ON ia.category_id = ac.id AND ia.attribute_id = a.id
					JOIN items i ON i.id = ia.item_id AND i.status = \'active\' 
					JOIN items_to_category ic ON ic.item_id = i.id 
					WHERE LOWER(ac.name) = '. $db->quote(mb_strtolower($attributes[0], 'UTF-8')) .' 
						AND ac.visible = \'true\'
						'. ($category_id ? 'AND ic.categories LIKE '. $db->quote('%'.$category_id.'%') : '') .'
					GROUP BY categories';
						
		return $db->fetchAll($query);
	}
}
?>
