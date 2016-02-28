<?php
class Model_Categories {
    public function getMain() {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name'))
					->where('visible = ?', 'true')
					->where('sub_of = ?', '0')
					->order('order_index ASC');		
		
					
		return $db->fetchAll($query);	
				
	}
	
	public function getWithChilds() {
	    $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name'))
					->where('visible = ?', 'true')
					->order('order_index ASC');
					
	    $return = array();
	    foreach($db->fetchAll($query) as $d) {
			$return[$d['sub_of']][$d['id']] = $d;
		}
		return $return;
	}
	
    public function get_all() {
     
		
	    $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name'))
					->where('visible = ?', 'true')
					->order('order_index ASC');
		
		
					
		//return $db->fetchAll($query);
	    $return = array();
	    foreach($db->fetchAll($query) as $d) {
			$return[$d['id']] = $d;
		}

		return $return;
	}
	
	public function get($id) {
		
		static $result = array();
		
		if(isset($result[$id])) return $result[$id];
	    
	    $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name'))
					->where('categories.id = ?', (int)$id)
					->limit(1, 0);
		
		$result[$id] = $db->fetchRow($query);
					
		return $result[$id];	
	}
	


	public function getCategoryParents($categories, $categoryID) {
		$return = '';
		if(isset($categories[$categoryID])) {
			$return .= $categoryID.',';
			$return .= Model_Categories::getCategoryParents($categories, $categories[$categoryID]['sub_of']);
		}
		
		return $return;
	}
	
    public function getCategories($id) {
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('categories')
					->joinLeft('categories_description', 'categories_description.id = categories.id AND categories_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name'))
					->where('visible = ?', 'true')
					->where('sub_of = ?', (int)$id)
					->order('order_index ASC');
		
		

		return $db->fetchAll($query);	
				
	}
	
	public function getItemsCategories($item_id) {
	    $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items_to_category')
					->where('item_id = ?', (int)$item_id);
		$array = array();
		foreach($db->fetchall($query) as $cat) {
		    $array[] = $cat['categories'];
		}
		return implode('|', $array);	
	}
	
   public function generateSelect($array, $selected=0, $subOf=0, $depth=0) {
		
		$text = '';
		
		if(isset($array[$subOf])) {
			
			foreach($array[$subOf] as $v) {
				if($depth > 0) continue;
				
				$text .= $v['id'] .'>&nbsp;';
				
				$text .= '&nbsp;'. $v['name'] .'|';
				$text .= Model_Categories::generateSelect($array, $selected, $v['id'], $depth+1);
			}
		
		}
		
		return $text;
	}
	
	public function addToItem($item_id, $category, $main_category) {
	    $db = JO_Db::getDefaultAdapter();
	
		$values = array();
		$query = "INSERT INTO items_to_category (item_id, categories) VALUES (". (int)$item_id  .", ',". (int)$main_category  .",'), (";	
	
		foreach($category as $cat) {
			$values[] = $item_id .', \','. (int)$main_category .', '. (int)$cat .',\'';
		}
	
		$query .= implode('), (', $values) .')';
		
		$db->query($query);
	}
	
    public function deleteItem($item_id) {
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->delete('items_to_category', array('item_id=?'=>$item_id));
	}
	
	public function deleteTempToItem($item_id) {
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->delete('temp_items_to_category', array('item_id=?'=>$item_id));
	}
	
	public function updateToItem($item_id, $category, $main_category) {
	    $db = JO_Db::getDefaultAdapter();
	
		$values = array();
		$query = "INSERT INTO temp_items_to_category (item_id, categories) VALUES (". (int)$item_id  .", ',". (int)$main_category  .",'), (";	
	
		foreach($category as $cat) {
			$values[] = $item_id .', \','. (int)$main_category .', '. (int)$cat .',\'';
		}
	
		$query .= implode('), (', $values) .')';
		
		$db->query($query);
	}
	
	public function getCategoriesByIds($arrId) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT c.*, cd.name, cd.lid
					FROM categories c
					LEFT JOIN categories_description cd ON cd.id = c.id AND cd.lid = \''. JO_Session::get('language_id') .'\'
					WHERE c.id IN ('. implode(',', (array)$arrId) .')';
		
		return $db->fetchAll($query);
	} 
}