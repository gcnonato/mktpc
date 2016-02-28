<?php
class Model_Pages {
    public function getPagesMenu() {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name','text'))
					->where('visible = ?', 'true')
					->where('menu = ?', 'true')
					->order('order_index ASC');
		
		
					
		$pages =  $db->fetchAll($query);	
		$return = array();
        foreach($pages as $d) {

				$return[$d['sub_of']][] = $d;
			
		}
		return $return;
    }
    
     public function getPagesFooter() {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name','text'))
					->where('visible = ?', 'true')
					->where('footer = ?', 'true')
					->order('order_index ASC');
		
		
					
		$pages =  $db->fetchAll($query);	
		$return = array();
        foreach($pages as $d) {

				$return[$d['sub_of']][] = $d;
			
		}
		return $return;
    }
	
	public static function getPages($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name','text'))
					->where('visible = ?', 'true')
					->order('order_index ASC');
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}

		return $db->fetchAll($query);
	}
    
     public function get($page_id) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name','text'))
					->where('visible = ?', 'true')
					->where('pages.id = ?', (int)$page_id)
					->limit(1, 0);
					
		return $db->fetchRow($query);
    }
    
 	public function getByKey($page) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name','text'))
					->where('`key` = ?', (string)$page)
					->limit(1, 0);
		
		return $db->fetchRow($query);
    }
	
	public static function getSubPages($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name','text'))
					->where('visible = ?', 'true')
					->where('sub_of = ?', (int)$id)
					->order('order_index ASC');
					
		return $db->fetchAll($query);
	}
	
	public static function getPageParents($id) {
	
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pages')
					->joinLeft('pages_description', 'pages_description.id = pages.id AND pages_description.lid = \''. JO_Session::get('language_id') .'\'', array('lid','name','text'))
					->where('visible = ?', 'true')
					->order('sub_of DESC');
					
		$results = $db->fetchAll($query);
		$return = array();
		
		$cnt = count($results);
		
		for($i = 0; $i < $cnt; $i++) {
			if($results[$i]['id'] == $id) {
				$return[] = $results[$i];
				$sub_of = $results[$i]['sub_of'];
				break;
			}
		}
		
		$i--;
		
		while($sub_of > 0 && $i >= 0) {
		
			if($sub_of == $results[$i]['id']) {
				
				$return[] = $results[$i];
				$sub_of = $results[$i]['sub_of'];
			}
			
			$i--;
		}
		$return = array_reverse($return);
		
		return $return;
	}
}