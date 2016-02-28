<?php
class Model_Bulletin {
    public function checkMail($email) {
        
	      $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('bulletin_emails', new JO_Db_Expr('COUNT(id)'))
					->where('email = ?', $email);
		
		return $db->fetchOne($query)>0 ? true : false;	
    }
    
    public function add($data) {
        if(Model_Bulletin::checkMail($data['email']))
        return false;
        
         $db = JO_Db::getDefaultAdapter();
        $db->insert('bulletin_emails', array(
			'firstname' => $data['fname'],
            'lastname'	 => $data['lname'],
            'email'		=> $data['email'],
            'bulletin_subscribe'	 => 'true'
		));
    }
}