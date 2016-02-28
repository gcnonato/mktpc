<?php
class Model_Attributes {
    public function getAttributes($smt = false) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('attributes')
					->where('visible = ?', 'true')
					->order('order_index ASC');
		
		if($smt) {
			$query->where($smt);
		}
	
		$return = array();
		foreach($db->fetchAll($query) as $d) {
			$return[$d['id']] = $d;
		}
		
		return $return;				
				
	}
 public function getAttributesCat($smt = false) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('attributes_categories')
					->where('visible = ?', 'true')
					->order('order_index ASC');
		
		if($smt) {
			$query->where($smt);
		}
	
					
		$return = array();
		foreach($db->fetchAll($query) as $d) {
			$return[$d['id']] = $d;
		}
		
		return $return;	
				
	}
	
	public function getAllWithCategories($where='') {
    
    	$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('attributes_categories', new JO_Db_Expr('attributes_categories.id AS head_id, attributes_categories.name AS head_name, attributes_categories.required, attributes_categories.type, attributes.*'))
					->joinLeft('attributes', 'attributes.category_id = attributes_categories.id AND attributes.visible = \'true\'')
					->where('attributes_categories.visible = ?', 'true')
					->order('attributes_categories.order_index ASC')
					->order('attributes.order_index ASC');
		
		if($where) {
			$query->where($where);
		}
		
		return $db->fetchAll($query);
	}

	
	public function get($id) {
	    
	    $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('countries')
					
					->where('id = ?', $id)
					->limit(1, 0);
		
		
					
		return $db->fetchRow($query);	
	}
	
    public function addToItem($item_id, $data) {
	    $db = JO_Db::getDefaultAdapter();
		
		$query = "INSERT INTO items_attributes (item_id, attribute_id, category_id) VALUES ('";
		
		$values = array();
		foreach($data as $k => $v) {
			if(is_array($v)) {
				foreach($v as $vl) {
					$values[] = (int)$item_id .'\', \''. $vl .'\', \''. $k;
				}
			} else {
				$values[] = (int)$item_id .'\', \''. strip_tags($v) .'\', \''. $k;
			}
		}
		
		$query .= implode('\'), (\'', $values) .'\')';
		
		$db->query($query);
	}
	
	public function deleteItem($item_id) {
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->delete('items_attributes', array('item_id=?'=>$item_id));
	}
	
	public function deleteTempToItem($item_id) {
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->delete('temp_items_attributes', array('item_id=?'=>$item_id));
	}
	
	public function updateToItem($item_id, $data) {
	    $db = JO_Db::getDefaultAdapter();
		
		$query = "INSERT INTO temp_items_attributes (item_id, attribute_id, category_id) VALUES ('";
		
		$values = array();
		foreach($data as $k => $v) {
			if(is_array($v)) {
				foreach($v as $vl) {
					$values[] = (int)$item_id .'\', \''. $vl .'\', \''. $k;
				}
			} else {
				$values[] = (int)$item_id .'\', \''. strip_tags($v) .'\', \''. $k;
			}
		}
		
		$query .= implode('\'), (\'', $values) .'\')';
		
		$db->query($query);
	}
	
}