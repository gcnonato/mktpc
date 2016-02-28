<?php
class Model_Faq{
    public static function getAll($item_id) {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items_faqs')
					->where('item_id = ?', (int)$item_id);
					
		return $db->fetchAll($query);	
				
    }
    
    public function countAll($item_id) {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items_faqs', new JO_Db_Expr('COUNT(item_id)'))
					->where('item_id = ?', (int)$item_id);
					
		return $db->fetchOne($query);	
    }
    
	public static function isOwner($user_id, $faq_id) {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('items_faqs', new JO_Db_Expr('COUNT(item_id)'))
					->where('id = ?', (int)$faq_id)
					->where('user_id = ?', (int)$user_id);
					
		return $db->fetchOne($query)>0 ? true : false;	
    }
    
    public static function delete($id) {
        $db = JO_Db::getDefaultAdapter();
        $db->delete('items_faqs', array('id=?'=>(int)$id));
    }
    
    public static function add($data) {
        $db = JO_Db::getDefaultAdapter();
        $db->insert('items_faqs', array(
        'item_id'	=> $data['item_id'],
        'user_id'	=> $data['user_id'],
        'question'	=> strip_tags($data['question']),
        'answer'	=> strip_tags($data['answer'], '<p>,<br>,<img>,<a>,<ul>,<ol>,<li>,<i>,<b>,<sub>,<sup>,<u>'),
        'datetime'	=> new JO_Db_Expr('NOW()')
        ));
    }
	
	public static function update($id, $data) {
		 $db = JO_Db::getDefaultAdapter();
		 
		 $db->update('items_faqs', array(
        'item_id'	=> $data['item_id'],
        'user_id'	=> $data['user_id'],
        'question'	=> strip_tags($data['question']),
        'answer'	=> strip_tags($data['answer'], '<p>,<br>,<img>,<a>,<ul>,<ol>,<li>,<i>,<b>,<sub>,<sup>,<u>')
        ), array('id = ?' => (int)$id));
	}
}