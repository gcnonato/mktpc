<?php
class Model_Countries {
    public function getCountries() {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('countries')
					->where('visible = ?', 'true')
					->order('order_index ASC');
		
		
					
		return $db->fetchAll($query);	
				
	}

	
	public function get($id) {
	    
	    $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('countries')
					
					->where('id = ?', (int)$id)
					->limit(1, 0);
		
		
					
		return $db->fetchRow($query);	
	}
	
}