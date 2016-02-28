<?php

class Model_Contacts {

	public static function addContact($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$domain = JO_Request::getInstance()->getDomain();
		$translate = WM_Translate::getInstance();
		
		$text = $translate->translate('Username') . ": " . $data['username'] . "
		" . $translate->translate('E-mail') . ": " . $data['email'] . "
		" . $translate->translate('Issue') . ": " . $data['issue'] . "
		" . $translate->translate('Description of issue') . ": " . $data['issue_details'] . "";
		
		$db->insert('contacts', array(
			'name' => $data['username'],
			'email' => $data['email'],
			'issue' => $data['issue'],
			'issue_id' => (int)$data['issue_id'],
			'short_text' => $text,
			'datetime' => new JO_Db_Expr('NOW()')
		));
		
		$contact_id = $db->lastInsertId();
		
		if($contact_id) {
			$mail = new JO_Mail;
			if(JO_Registry::get('mail_smtp')) {
				$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
			}
			$mail->setFrom($data['email']);
			$mail->setSubject("[".$domain."] " . $translate->translate('Contact form') . ' [' . $contact_id . ']');
			
			$mail->setHTML(nl2br($text));
			
			$result = (int)$mail->send(array(JO_Registry::get('admin_mail')), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
			
			return $result;
			
		}
		
	}
	
}

?>