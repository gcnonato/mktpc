<?php

class WM_Gettranslate {
	
	public static function getTranslate() {
		self::initDB();
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language_keywords', array('keyword'))
					->joinLeft('language_translate', 'language_keywords.language_keywords_id = language_translate.language_keywords_id AND language_translate.language_id = ' . (int)JO_Registry::get('config_language_id'), array('translate' => new JO_Db_Expr('IF(language_translate.keyword != \'\',language_translate.keyword,language_keywords.keyword)')))
					->where('language_keywords.module = ?', JO_Request::getInstance()->getModule())
					/*->order('language_keywords.keyword ASC')*/;
					
		$result = $db->fetchPairs($query);
		
		foreach($result AS $k=>$v) {
			$result[$k] = html_entity_decode($v, ENT_QUOTES, 'utf-8');
		}

		return $result;
	}
	
	public static function getTranslateJs() {
		self::initDB();
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language_keywords', array('keyword'))
					->joinLeft('language_translate', 'language_keywords.language_keywords_id = language_translate.language_keywords_id AND language_translate.language_id = ' . (int)JO_Registry::get('config_language_id'), array('translate' => new JO_Db_Expr('IF(language_translate.keyword != \'\',language_translate.keyword,language_keywords.keyword)')))
					->where('language_keywords.module = ?', JO_Request::getInstance()->getModule())
					/*->order('language_keywords.keyword ASC')*/;
					
		$result = $db->fetchPairs($query);
		
		foreach($result AS $k=>$v) {
			$result[$k] = html_entity_decode($v, ENT_QUOTES, 'utf-8');
		}

		return $result;
	}
	
	private function initDB() {
		$db = JO_Db::getDefaultAdapter();
		$db->query("CREATE TABLE IF NOT EXISTS `language_keywords` (
		  `language_keywords_id` int(11) NOT NULL auto_increment,
		  `key` char(32) collate utf8_unicode_ci NOT NULL,
		  `keyword` text character set utf8 collate utf8_bin NOT NULL,
		  `module` varchar(128) collate utf8_unicode_ci NOT NULL,
		  /*`controller` varchar(128) collate utf8_unicode_ci NOT NULL,*/
		  PRIMARY KEY  (`language_keywords_id`),
		  KEY `module` (`module`),
		  KEY `key` (`key`),
		  /*KEY `controller` (`controller`),*/
		  FULLTEXT KEY `keyword` (`keyword`)
		) ENGINE=MyISAM;");
		$db->query("CREATE TABLE IF NOT EXISTS `language_translate` (
		  `language_keywords_id` int(11) NOT NULL auto_increment,
		  `language_id` int(11) NOT NULL,
		  `keyword` varchar(255) collate utf8_unicode_ci NOT NULL,
		  KEY `language_id` (`language_id`),
		  KEY `language_keywords_id` (`language_keywords_id`)
		) ENGINE=MyISAM;");
	}

}

?>