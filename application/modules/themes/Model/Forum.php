<?php

class Model_Forum {
	
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		
        $query = 'SELECT * FROM forum WHERE status = \'true\' ORDER BY order_index ASC';	
		
		return $db->fetchAll($query);	
	}
	
	public static function getAllThreads() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT f.name AS topic, fc.*, 
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE reply_to = fc.id
					LIMIT 1) AS cnt, 
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE user_id = u.user_id
					LIMIT 1) AS usr_posts, (SELECT datetime 
					FROM forum_comments 
					WHERE user_id = ul.user_id AND reply_to = fc.id
					ORDER BY datetime DESC
					LIMIT 1) AS last_post, u.user_id AS owner_id, u.avatar, u.badges, u.username AS owner,
					ul.user_id AS luser_id, ul.username AS lusername, ul.avatar AS lavatar
				FROM forum f
				JOIN forum_comments fc ON fc.thread_id = f.id AND fc.reply_to = 0
				JOIN users u ON u.user_id = fc.user_id
				LEFT JOIN users ul ON ul.user_id = (SELECT m.user_id 
								FROM forum_comments m 
								WHERE m.reply_to = fc.id
								ORDER BY m.datetime DESC
								LIMIT 1)
				WHERE f.status = \'true\'
				ORDER BY fc.datetime DESC';
		
		return $db->fetchAll($query);
	}
	
	public static function getThreadComments($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, f.name as thread_name, 
				(SELECT COUNT(id) 
				FROM forum_comments 
				WHERE reply_to = fc.id 
				LIMIT 1) AS cnt, 
				(SELECT COUNT(id) 
				FROM forum_comments 
				WHERE user_id = u.user_id 
				LIMIT 1) AS usr_posts, 
				(SELECT datetime 
				FROM forum_comments 
				WHERE user_id = ul.user_id AND reply_to = fc.id
				ORDER BY datetime DESC 
				LIMIT 1) AS last_post, 
				u.user_id AS owner_id, u.avatar, u.badges, u.username AS owner, 
				ul.user_id AS luser_id, ul.username AS lusername, ul.avatar AS lavatar 
			FROM forum_comments fc 
			JOIN forum f ON fc.thread_id = f.id AND f.status = \'true\' 
			JOIN users u ON u.user_id = fc.user_id 
			LEFT JOIN users ul ON ul.user_id = 
				(SELECT m.user_id 
				FROM forum_comments m 
				WHERE m.reply_to = fc.id 
				ORDER BY m.datetime DESC 
				LIMIT 1) 
			WHERE  fc.thread_id = \''. (int)$id .'\' AND fc.reply_to = 0
			ORDER BY fc.datetime DESC';
		
		return $db->fetchAll($query);
	}
	
	public function getThread($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT * FROM forum_comments WHERE id = \''. (int)$id .'\''; 
		
		return $db->fetchRow($query);
	}
	
	
	public static function getSubComments($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, u.avatar, u.username, u.badges,
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE user_id = u.user_id
					LIMIT 1) AS usr_posts
				FROM forum_comments fc
				JOIN forum f ON f.id = fc.thread_id AND f.status = \'true\'
				JOIN users u ON u.user_id = fc.user_id
				WHERE fc.id = \''. (int)$id .'\'
				UNION ALL
				(SELECT fc.*, u.avatar, u.username, u.badges,
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE user_id = u.user_id
					LIMIT 1) AS usr_posts
				FROM forum_comments fc
				JOIN forum f ON f.id = fc.thread_id AND f.status = \'true\'
				JOIN users u ON u.user_id = fc.user_id
				WHERE fc.reply_to = \''. (int)$id .'\')
				ORDER BY datetime ASC';
		
		return $db->fetchAll($query);
	}
	
	public static function setThread($topic_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'INSERT INTO forum_comments (`thread_id`, `user_id`, `name`, `comment`, `datetime`, `notify`, `reply_to`) 
				VALUES ('. $db->quote($topic_id) .', '. $db->quote($data['user_id']) .', '. $db->quote($data['name']) .', '. $db->quote($data['comment']) .', NOW(), \''. $data['notify'] .'\', '. $db->quote($data['reply_to']) .')';
		
		$db->query($query);
		
		$last_id = $db->lastInsertId();
		
		$request = JO_Request::getInstance();
		$domain = $request->getDomain();
		$translate = JO_Translate::getInstance();				$query = "SELECT u.email, u.username 				FROM forum_comments fc				JOIN users u ON u.user_id = fc.user_id				WHERE fc.id = '". (int)$data['reply_to'] ."' AND fc.notify = 'true'";						$user = $db->fetchRow($query);		
				if($user) {
			$mail = new JO_Mail;
			if(JO_Registry::get('mail_smtp')) {
				$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
			}
			$mail->setFrom('no-reply@'.$domain);
			
			$href = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/'. $topic_id);

			$not_template = Model_Notification::getNotification('comment_reply_to');
			
			if($not_template) {
				$title = $not_template['title'];
				$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
				$html = str_replace('{USERNAME}', $user['username'], $html);
				$html = str_replace('{URL}', $href, $html);
			} else {
				$title = "[".$domain."] " . $translate->translate('Have new reply to your comment');
				$html = nl2br($translate->translate('A reply is added to your comment').'
						
				 '.$href.'
				');
			}
			
			$mail->setSubject($title);
			
			$mail->setHTML($html);
			
			$mail->send(array($user['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));		}
		
		return $last_id;
	}
	
	 public static function get($id) {
    	$db = JO_Db::getDefaultAdapter();
    	$query = $db->select()
    				->from('forum_comments')
    				->where('id = ?', (int)$id)
    				->limit(1);
    	return $db->fetchRow($query);
    }
	
	public static function report($id) {
    	
    	if(!JO_Session::get('user_id')) {
    		return false;
    	}
    	
    	$info = self::get($id);
    	if(!$info) {
    		return;
    	}
    	
    	$user = Model_Users::getUser($info['user_id']);
		if(!$user) {
    		$user = array('username' => '');
    	}
    	
    	$db = JO_Db::getDefaultAdapter();
    	$db->update('forum_comments', array(
    		'report_by' => JO_Session::get('user_id')
    	), array('id = ?' => (int)$id));
    	
    	
    	$request = JO_Request::getInstance();
		
		$domain = $request->getDomain();
		$translate = JO_Translate::getInstance();
		
		$mail = new JO_Mail;
		if(JO_Registry::get('mail_smtp')) {
			$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
		}
		$mail->setFrom('no-reply@'.$domain);
		
		$not_template = Model_Notification::getNotification('comment_report');
		
		$href = '<a href="' . WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/' . ($info['reply_to'] > 0 ? $info['reply_to'] : $info['id'] )) .'">' . $translate->translate('Comment') . '</a>';
		
		if($not_template) {
			$title = $not_template['title'];
			$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
			$html = str_replace('{USERNAME}', $user['username'], $html);
			$html = str_replace('{REPORT}', JO_Session::get('username'), $html);
			$html = str_replace('{URL}', $href, $html);
		} else {
			$title = "[".$domain."] " . $translate->translate('Have new reported comment');
			$html = nl2br(JO_Session::get('username').'
					
			 =======================================
			'.$translate->translate('Report about irregularity in comment.'));
		}
		
		$mail->setSubject($title);
		$mail->setHTML($html);
		
		$mail->send(array(JO_Registry::get('report_mail')), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
		
		return true;
    }

	public static function getLastThreads() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, 
				(SELECT COUNT(id) 
				FROM forum_comments 
				WHERE reply_to = fc.id 
				LIMIT 1) AS cnt, 
				(SELECT datetime 
				FROM forum_comments 
				WHERE user_id = ul.user_id AND reply_to = fc.id
				ORDER BY datetime DESC
				LIMIT 1) AS last_post, ul.username AS lusername 
			FROM forum_comments fc 
			JOIN forum f ON fc.thread_id = f.id AND f.status = \'true\' 
			JOIN users u ON u.user_id = fc.user_id 
			LEFT JOIN users ul ON ul.user_id = 
				(SELECT m.user_id 
				FROM forum_comments m 
				WHERE m.reply_to = fc.id 
				ORDER BY m.datetime DESC 
				LIMIT 1) 
			WHERE fc.reply_to = 0
			ORDER BY last_post DESC
			LIMIT 10';
		
		return $db->fetchAll($query);
	}
	
	public static function getRecentThreads($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, 
				(SELECT COUNT(id) 
				FROM forum_comments 
				WHERE reply_to = fc.id 
				LIMIT 1) AS cnt, 
				(SELECT datetime 
				FROM forum_comments 
				WHERE user_id = ul.user_id AND reply_to = fc.id
				ORDER BY datetime DESC
				LIMIT 1) AS last_post, ul.username AS lusername 
			FROM forum_comments fc 
			JOIN forum f ON fc.thread_id = f.id AND f.status = \'true\' 
			JOIN users u ON u.user_id = fc.user_id 
			LEFT JOIN users ul ON ul.user_id = 
				(SELECT m.user_id 
				FROM forum_comments m 
				WHERE m.reply_to = fc.id 
				ORDER BY m.datetime DESC 
				LIMIT 1) 
			WHERE fc.reply_to = 0 AND fc.user_id = \''. (int)$id .'\'
			ORDER BY last_post DESC
			LIMIT 10';
		
		return $db->fetchAll($query);
	}

	public static function getRss() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, u.username, f.name AS thread_name, 
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE reply_to = fc.id 
					LIMIT 1) AS cnt
				FROM forum_comments fc
				JOIN forum f ON f.id = fc.thread_id AND f.status = \'true\'
				JOIN users u ON u.user_id = fc.user_id
				WHERE fc.report_by = 0
				ORDER BY fc.datetime DESC';
				
		return $db->fetchAll($query);
	}
	
	public static function getSearch($keyword) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, u.avatar, u.username, u.badges,
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE user_id = u.user_id
					LIMIT 1) AS usr_posts
				FROM forum_comments fc
				JOIN forum f ON f.id = fc.thread_id AND f.status = \'true\'
				JOIN users u ON u.user_id = fc.user_id
				WHERE fc.name LIKE '. $db->quote('%'. $keyword .'%') .'
					OR fc.comment LIKE '. $db->quote('%'. $keyword .'%') .'
				ORDER BY datetime ASC';
	
		return $db->fetchAll($query);
	}
	
	public static function getRecentByUser($username) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT fc.*, u.avatar, u.username, u.badges,
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE user_id = u.user_id
					LIMIT 1) AS usr_posts
				FROM forum_comments fc
				JOIN forum f ON f.id = fc.thread_id AND f.status = \'true\'
				JOIN users u ON u.user_id = fc.user_id
				WHERE u.username = '. $db->quote($username) .' AND fc.reply_to > 0
				ORDER BY datetime DESC';
	
		return $db->fetchAll($query);
	}
	
	public static function getThreadsByUser($username) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'SELECT f.name AS topic, fc.*, 
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE reply_to = fc.id
					LIMIT 1) AS cnt, 
					(SELECT COUNT(id) 
					FROM forum_comments 
					WHERE user_id = u.user_id
					LIMIT 1) AS usr_posts, (SELECT datetime 
					FROM forum_comments 
					WHERE user_id = ul.user_id AND reply_to = fc.id
					ORDER BY datetime DESC
					LIMIT 1) AS last_post, u.user_id AS owner_id, u.avatar, u.badges, u.username AS owner,
					ul.user_id AS luser_id, ul.username AS lusername, ul.avatar AS lavatar
				FROM forum f
				JOIN forum_comments fc ON fc.thread_id = f.id AND fc.reply_to = 0
				JOIN users u ON u.user_id = fc.user_id AND u.username = '. $db->quote($username) .'
				LEFT JOIN users ul ON ul.user_id = (SELECT m.user_id 
								FROM forum_comments m 
								WHERE m.reply_to = fc.id
								ORDER BY m.datetime DESC
								LIMIT 1)
				WHERE f.status = \'true\'
				ORDER BY fc.datetime DESC';
	
		return $db->fetchAll($query);
	}
}

?>