<?php 
class Model_Comments {
	
    public static function getAll($start=0, $limit=0, $where='', $withReply=false, $order='id DESC') {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('items_comments')
					->joinLeft(Model_Users::getPrefixDB().'users', 'items_comments.user_id = users.user_id', array('username', 'avatar', 'badges', 'item_user_id' => 'user_id'))
					->joinLeft(Model_Users::getPrefixDB().'items', 'items.id = items_comments.item_id', array('name'))
					->order($order);
					
		if($start || $limit) {
			$query->limit($limit, $start);
		}
					
		if($where) {
			$query->where($where);
		}
//		echo $query; exit;
		$response = $db->fetchAll($query);
		
		$results = array();
		if($response) {
			foreach($response AS $d) {
				$d['comment'] = self::replaceEmoticons($d['comment']);
				if($withReply) {
					$d['reply'] = self::getAll(0, 0, "reply_to = '" . (int)$d['id'] . "'", false, 'id ASC');
				} else {
					$d['reply'] = array();
				}
				$results[] = $d;
			}
		}
		
		
		return $results;

    }
    
    public static function getTotal($where='') {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('items_comments', 'COUNT(id)')
					->joinLeft(Model_Users::getPrefixDB().'users', 'items_comments.user_id = users.user_id', array())
					->limit(1);
					
		if($where) {
			$query->where($where);
		}
		
		return $db->fetchOne($query);

    }
    
    public static function get($id) {
    	$db = JO_Db::getDefaultAdapter();
    	$query = $db->select()
    				->from('items_comments')
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
    	$db->update('items_comments', array(
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
		
		$href = '<a href="' . WM_Router::create($request->getBaseUrl() . '?controller=items&action=comments&item_id=' . $info['item_id'] . '&filter=' . ($info['reply_to'] ? $info['reply_to'] : $info['id'])) . '">' . $info['item_name'] . '</a>';
		
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
    
    public static function add($data) {
    	$db = JO_Db::getDefaultAdapter();
    	$db->insert('items_comments', array(
    		'owner_id' => (int)$data['owner_id'],
    		'item_id' => (int)$data['item_id'],
    		'item_name' => $data['item_name'],
    		'user_id' => (int)$data['user_id'],
    		'comment' => strip_tags(html_entity_decode($data['comment']),'<br><p><span><h1><h2><h3><a><img><big><small><ul><ol><li><quote><u><i><b><code><pre>'),
    		'datetime' => new JO_Db_Expr('NOW()'),
    		'notify' => $data['notify'],
    		'reply_to' => $data['reply_to']
    	));
    	
    	return $db->lastInsertId();
    }
    
    
    public static function replaceEmoticons($comment) {
    	
    	static $orig, $repl;
    	
    	if (!isset($orig)) {
    		$orig = $repl = array();
    		$db = JO_Db::getDefaultAdapter();
    		
    		$tables = $db->listTables();
			if(!in_array('smiles', $tables)) return $comment;
    		
    		$all_list = Model_Smiles::getSmiles();
    		$smilies = array();
    		if($all_list) {
    			$smilies = $all_list;
    		}
    		
	    	if (count($smilies)) {
				usort($smilies, array('Model_Comments', '_smiley_sort'));
			}
			
	    	for ($i = 0; $i < count($smilies); $i++) {
	    		if(trim($smilies[$i]['code']) && file_exists(BASE_PATH . '/uploads/' . $smilies[$i]['photo']) && is_file(BASE_PATH . '/uploads/' . $smilies[$i]['photo'])) {
					$codes = explode(',', $smilies[$i]['code']);
					foreach($codes AS $code) {
						$code = trim($code);
						if($code) {
			    			$orig[] = "/(?<=.\W|\W.|^\W)" . self::_preg_quote($code, "/") . "(?=.\W|\W.|\W$)/";
							$repl[] = '<img src="uploads/' . $smilies[$i]['photo'] . '" alt="' . $smilies[$i]['name'] . '" border="0" />';
						}
					}
				}
			}
    	}
    	
	    if (count($orig)) {
			$comment = preg_replace($orig, $repl, ' ' . $comment . ' ');
			$comment = substr($comment, 1, -1);
		}
    	
    	return $comment;
    }
	
	private static function _smiley_sort($a, $b) {
		if ( strlen($a['code']) == strlen($b['code']) ) {
			return 0;
		}
		return ( mb_strlen($a['code'], 'utf-8') > mb_strlen($b['code'], 'utf-8') ) ? -1 : 1;
	}
	
	private static function _preg_quote($str, $delimiter) {
		$text = preg_quote($str);
		$text = str_replace($delimiter, '\\' . $delimiter, $text); 
		return $text;
	}
}







