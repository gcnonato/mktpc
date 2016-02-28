<?php
class Model_Collections {
    public function getByUser($start=0, $limit=2, $user_id, $order = false, $public = true) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('collections')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = collections.user_id', 'users.username')
					->where('collections.user_id = ?', (int)$user_id)
					
					->group('collections.id')
					->order($order)
					->limit($limit, $start);		if(JO_Session::get('user_id') != $user_id) {			$query->where('public = ?', $public ? 'true' : 'false');		}
		
	//	if($where)
	//	    $query->where($where);
			
		return $db->fetchAll($query);			
    }
    
	public function countByUser($user_id, $public = true)
	{
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('collections', new JO_Db_Expr('COUNT(collections.id)'))
					->group('collections.id')
					->where('collections.user_id = ?', (int)$user_id)
					->where('public = ?', $public ? 'true' : 'false');

		return $db->fetchOne($query);
	}
	
	public function getAll($start=0, $limit=2, $order = 'id desc', $public=false, $where = false, $bytype = false, $search = false) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('collections')
					->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = collections.user_id', 'users.username')
					->group('collections.id')
					->limit($limit, $start)
					->order($order);
		if($public)
		    $query->where('public = ?', 'true');
		
		if($where)
		    $query->where($where);
		    
		
		/*    
		if($bytype){
		    $return = array();
		    foreach($db->fetchAll($query) as $res) {
		        $return[$res['public']][] = $res;
		    } 
		    return $return;
		}
			*/	
		return $db->fetchAll($query);			
    }
    
	public function countCollections($public=false, $where = false) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('collections', new JO_Db_Expr('COUNT(collections.id)'))
					->group('collections.id') 
					->order('id desc');
		if($public)
		    $query->where('public = ?', 'true');
		
		if($where)
		    $query->where($where);
	
		return $db->fetchOne($query);			
    }
    
    public function get($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
		         ->from('collections')
				 ->joinLeft(Model_Users::getPrefixDB().'users', 'users.user_id = collections.user_id', array('users.username', 'users.firstname', 'users.lastname'))
		         ->where('collections.id = ?', (int)$id);

		return $db->fetchRow($query);	
	}
	
	public function add($data) { 
		
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->insert('collections', 
    	    array(
    	    'user_id'	=>    JO_Session::get('user_id'),
    	    'name'		=>    $data['name'],
    	    'text'		=>    $data['description'],
    	    'datetime'	=>    new JO_Db_Expr('NOW()'),
    	    'public'	=>    $data['publically_visible']    
    	    )
	    );
	    
		
		return $db->lastInsertId(); 
	}
	
    public function edit($data) { 
		
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->update('collections', 
    	    array(
    	    'name'		=>    $data['name'],
    	    'text'		=>    $data['description'],
    	    'public'	=>    $data['publically_visible']    
    	    ), array('id = ?'=>$data['collection_id'])
	    );
	    
		
		return $db->lastInsertId(); 
	}
	
	public function editImage($collectionID, $image) {
	    
	    $db = JO_Db::getDefaultAdapter();
	    
	    $db->update('collections', array('photo'=>(string)$image), array('id=?'=> (int)$collectionID));
	}
	
	public function deleteBookmark($collectionID, $itemID) {
		$db = JO_Db::getDefaultAdapter();
		$delete = $db->delete('items_collections', array('collection_id = ?'=>(int)$collectionID, 'item_id =?' => (int)$itemID));
	
		if($delete) {
			Model_Collections::incCollection($collectionID, '-');
		} 
		
		return false;
	}
	
    public function deleteCollection($collectionID) {
		$db = JO_Db::getDefaultAdapter();		
		$db->delete('collections', array('id = ?'=>(int)$collectionID));
		$db->delete('items_collections', array('collection_id = ?'=>(int)$collectionID));
	    $db->delete('collections_rates', array('collection_id = ?'=>(int)$collectionID));

		
		return false;
	}
	
	public function incCollection($collectionID, $sign='+') {
		$db = JO_Db::getDefaultAdapter();
		$db->update('collections', array('items'=>new JO_Db_Expr('items '.$sign.' 1')), array('id =?'=>(int)$collectionID));
				
		return true;
	}
	
    public function isRate($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
		     ->from('collections_rates')
		     ->where('collection_id = ?', (int)$id)
		     ->where('user_id = ?', (int)JO_Session::get('user_id'));
		
		$result = $db->fetchRow($query);
		
		if(count($result)==0) {
			return false;
		}

		return $result;
	}
	
	public function rate($collection, $id, $rate) {
		
		$row = Model_Collections::isRate($id);
		if(is_array($row)) {
			return $collection;
		}
		
		$collection['votes'] = $collection['votes'] + 1;
		$collection['score'] = $collection['score'] + $rate;
		$collection['rating'] = $collection['score'] / $collection['votes'];
		$collection['rating'] = round($collection['rating']);
		
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('collections',
		array(
		'rating'=>$collection['rating'],
		'score'=>$collection['score'],
		'votes'=>$collection['votes']
		),
		array('id = ?'=>(int)$id));
		
		$db->insert('collections_rates',
		array(
		'collection_id'=>(int)$id,
		'user_id'=>(int)JO_Session::get('user_id'),
		'rate'=>$rate,
		'datetime'=>new JO_Db_Expr('NOW()')
		));
		
		
		return $collection;
	}
	
    public function isInCollection($id, $collectionID) {
        $db = JO_Db::getDefaultAdapter();
        
        $query = $db->select()
                    ->from('items_collections', new JO_Db_Expr('COUNT(collection_id)'))
                    ->where('item_id = ?', (int)$id)
                    ->where('collection_id = ?', (int)$collectionID);
		$res = $db->fetchOne($query); 
		
		return $res > 0 ? true : false;
	}
	 
	public function bookmark($id, $collectionID) {
		$db = JO_Db::getDefaultAdapter();
		
		
		
		if(Model_Collections::isInCollection($id, $collectionID)) {
			return true;
		}
		
		$db->insert('items_collections', array('item_id'=>$id, 'collection_id' => $collectionID));
		
		
		Model_Collections::incCollection($collectionID, '+');
		
		return true;
	}
}