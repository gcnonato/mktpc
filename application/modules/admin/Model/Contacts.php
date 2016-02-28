<?php

class Model_Contacts {
	
	public static function sendContact($id, $data = array()) {
		$info = self::getContact($id);
		if(!$info) {
			return false;
		}
		
		$db = JO_Db::getDefaultAdapter();
		$db->update('contacts', array(
			'answer' => $data['answer'],
			'answer_datetime' => new JO_Db_Expr('NOW()')
		),array('id = ?' => (int)$id));
		
		$request = JO_Request::getInstance();
		
		$domain = $request->getDomain();
		$translate = JO_Translate::getInstance();
		
		$mail = new JO_Mail;
		if(JO_Registry::get('mail_smtp')) {
			$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
		}
		$mail->setFrom('no-reply@'.$domain);
		$mail->setSubject("[".$domain."] " . $translate->translate('Contact form'));
		
		$html = nl2br($data['answer'].'
				
		'.$info['name'].' '.$translate->translate('wrote').' =======================================
		'.$info['short_text']);
		$mail->setHTML($html);
		
		$result = (int)$mail->send(array($info['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
				
		return $result;
	}
	
	public static function getContacts($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('contacts', array('*', new JO_Db_Expr("IF(answer_datetime = '0000-00-00', '9999-99-99', `answer_datetime`) AS answer_datetime_new")))
					->order(new JO_Db_Expr("`answer_datetime_new` DESC, `datetime` DESC"));
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['filter_answer_datetime'])) {
			$query->where('answer_datetime = ?', $data['filter_answer_datetime']);
		}
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTotalContacts($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('contacts', 'COUNT(id)');

		if(isset($data['filter_answer_datetime'])) {
			$query->where('answer_datetime = ?', $data['filter_answer_datetime']);
		}
		
		return $db->fetchOne($query);	
	}
	
	public static function getContact($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('contacts')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
		
	}
	
	public static function deleteContact($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('contacts', array('id = ?' => (int)$id));
	}

}

?>