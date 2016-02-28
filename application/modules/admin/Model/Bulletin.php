<?php

class Model_Bulletin {

	public static function createBulletin($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('bulletin', array(
			'name' => $data['name'],
			'text' => $data['text'],
			'datetime' => new JO_Db_Expr('NOW()'),
			'send_to' => $data['send_to'],
			'send_id' => 0
		));
		$id = $db->lastInsertId();
		
		$emails = Model_Bulletinemails::getEmails(array(
			'filter_bulletin_subscribe' => 'true'
		));
		
		$domain = JO_Request::getInstance()->getDomain();
		
		$send_to = 0;
		
		if($emails) {
			foreach($emails AS $email) {
				
				$not_template = Model_Notificationtemplates::get('bulletin_email');
				
				$mail = new JO_Mail;
				if(JO_Registry::get('mail_smtp')) {
					$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
				}
				$mail->setFrom('no-reply@'.$domain);
				$mail->setSubject("[".$domain."] " . $data['name']);
				
				if($not_template) {
					$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
					$html = str_replace('{FIRSTNAME}', $email['firstname'], $html);
					$html = str_replace('{LASTNAME}', $email['lastname'], $html);
					$html = str_replace('{MESSAGE}', html_entity_decode($data['text'], ENT_QUOTES, 'utf-8'), $html);
				} else {
					$html = html_entity_decode($data['text'], ENT_QUOTES, 'utf-8');
				}
				
				$mail->setHTML($html);
				$result = $mail->send(array($email['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
				if($result) {
					$send_to++;
				}
				unset($mail);
			}
		}
		
		$db->update('bulletin', array(
			'readed' => $send_to
		), array('id = ?' => $id));
		
		return $id;
	}
	
	public static function getBulletines($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('bulletin')
					->order('id DESC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTotalBulletines($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('bulletin', 'COUNT(id)');
		
		return $db->fetchOne($query);
		
	}
	
	public static function getBulletin($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('bulletin')
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}
	
	public static function deleteBulletin($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('bulletin', array('id = ?' => (int)$id));
	}

}

?>