<?php

class Model_Extensions_Topbanner {

	public static function install() {
		$db = JO_Db::getDefaultAdapter();
		$db->query("
			CREATE TABLE IF NOT EXISTS `topbanner` (
			  `id` int(11) NOT NULL auto_increment,
			  `name` varchar(255) default NULL,
			  `url` varchar(255) default NULL,
			  `html` text,
			  `background` varchar(7) NOT NULL,
			  `photo` varchar(255) NOT NULL,
			  `from` date default NULL,
			  `to` date default NULL,
			  `close` enum('false','true') default 'true',
			  `views` int(11) NOT NULL default '0',
			  `clicks` int(11) NOT NULL default '0',
			  `width` int(11) NOT NULL,
			  `height` int(11) NOT NULL,
			  PRIMARY KEY  (`id`)
			) ENGINE=MyISAM;
		");
	}
	
	/////////////////////////////////////////////////
	
	public static function create($data) {
		$db = JO_Db::getDefaultAdapter();
		
		if(isset($data['topbanner'])) {
			Model_Settings::updateAll(array('topbanner' => $data['topbanner']));
		}
		
		$db->insert('topbanner', array(
			'name' => $data['name'],
			'url' => $data['url'],
			'html' => $data['html'],
			'background' => $data['background'],
			'photo' => $data['photo'],
			'from' => ($data['from'] ? JO_Date::getInstance($data['from'], 'yy-mm-dd', true)->toString() : '0000-00-00'),
			'to' => ($data['to'] ? JO_Date::getInstance($data['to'], 'yy-mm-dd', true)->toString() : '0000-00-00'),
			'close' => $data['close'],
			'width' => (int)$data['width'],
			'height' => (int)$data['height'],
		    'cookie' => (int)$data['cookie']
		));
		
		return $db->lastInsertId();
	}
	
	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		if(isset($data['topbanner'])) {
			Model_Settings::updateAll(array('topbanner' => $data['topbanner']));
		}
		
		return $db->update('topbanner', array(
			'name' => $data['name'],
			'url' => $data['url'],
			'html' => $data['html'],
			'background' => $data['background'],
			'photo' => $data['photo'],
			'from' => ($data['from'] ? JO_Date::getInstance($data['from'], 'yy-mm-dd', true)->toString() : '0000-00-00'),
			'to' => ($data['to'] ? JO_Date::getInstance($data['to'], 'yy-mm-dd', true)->toString() : '0000-00-00'),
			'close' => $data['close'],
			'width' => (int)$data['width'],
			'height' => (int)$data['height'],
		    'cookie' => (int)$data['cookie']
		), array('id = ?' => (int)$id));
	}
	
	public static function getAll($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('topbanner')
					->order('id DESC');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotal($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('topbanner', 'COUNT(id)')
					->limit(1);
		
		return $db->fetchOne($query);
	}
	
	public static function get($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('topbanner')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	
	public static function changeStatus($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('topbanner', array(
			'close' => new JO_Db_Expr("IF(close = 'true', 'false', 'true')")
		), array('id = ?' => (int)$id));
	}
	
	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('topbanner', array('id = ?' => (int)$id));
	}
	
	
}

?>