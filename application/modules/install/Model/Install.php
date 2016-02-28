<?php

class Model_Install {

	public static function installWithDemo(JO_Db_Adapter_Abstract $db) {
		
		mysql_connect($db->getConfig('host'),$db->getConfig('username'),$db->getConfig('password'));
		mysql_select_db($db->getConfig('dbname'));
		mysql_set_charset('utf8');
		
		$result = self::installWithoutDemo($db);
		
		if(!$result) return false;
		
		self::unlink(BASE_PATH . '/uploads/', false);
		self::recursiveCopy(APPLICATION_PATH . '/modules/install/uploads/', BASE_PATH . '/uploads/');
		
		$structure = APPLICATION_PATH . '/modules/install/data.sql';
		$queryes = self::getQueryes(file($structure));

		$results = array();
		
		foreach($queryes AS $query) {
			if(trim($query)) {
				try {
					/*$results[] = */(bool)mysql_query($query);
				} catch (JO_Exception $e) {
					/*$results[] = */false;
				}
				
			}
		}
		
		return !in_array(false, $results);
		
	}
	
	public static function installWithoutDemo(JO_Db_Adapter_Abstract $db) {
		
		mysql_connect($db->getConfig('host'),$db->getConfig('username'),$db->getConfig('password'));
		mysql_select_db($db->getConfig('dbname'));
		mysql_set_charset('utf8');
		$structure = APPLICATION_PATH . '/modules/install/structure.sql';
		if(!file_exists($structure)) {
			return false;
		}
		
		$queryes = self::getQueryes(file($structure));

		$results = array();
		
		foreach($queryes AS $query) {
			if(trim($query)) {
				try {
					/*$results[] = */(bool)mysql_query($query);
				} catch (JO_Exception $e) {
					/*$results[] = false;*/
				}
				
			}
		}
		
		$request = JO_Request::getInstance();
		
		$results[] = $db->insert('users', array(
			'user_id' => 1,
		     'username'	=> $request->getPost('username'),
		     'password'	=> md5(md5($request->getPost('password'))),
		     'register_datetime'	=> new JO_Db_Expr('NOW()'),
		     'status'	=> 'activate',
		     'groups'	=> 'a:1:{i:2;s:2:"on";}'
	     
	     ));
	     
	     /*$results[] = */$db->update('system', array(
	     	'value' => $request->getPost('admin_mail')
	     ), array('`key` = ?' => 'admin_mail'));
	     /*$results[] = */$db->update('system', array(
	     	'value' => $request->getPost('report_mail')
	     ), array('`key` = ?' => 'report_mail'));
	     
	     
		if(!in_array(false, $results)) {
			$db_set = "
	db.adapter = \"MYSQLi\"
	db.params.host = \"".$db->getConfig('host')."\"
	db.params.username = \"".$db->getConfig('username')."\"
	db.params.password = \"".$db->getConfig('password')."\"
	db.params.dbname = \"".$db->getConfig('dbname')."\"
	db.params.charset =\"utf8\"";
			
			$results[] = (bool)@file_put_contents(APPLICATION_PATH . '/config/config_db.ini', $db_set);
		}
		return !in_array(false, $results);
		
	}
	
	private function getQueryes(array $data) {
		$queryes = array();
		$i=0;
		foreach($data AS $q) {
			$q = trim($q);
			$queryes[$i] = isset($queryes[$i]) ? $queryes[$i] : '';
			$queryes[$i] .= $q;
			if(substr($q, strlen($q)-1) == ';') {
				$i++;
			}
		} 
		return $queryes;
	}
	
	private function rename_if_exists($dir, $filename) {
    	$dir = rtrim($dir, '/');
	    $ext = strrchr($filename, '.');
	    $prefix = substr($filename, 0, -strlen($ext));
	    $i = 0;
	    while(file_exists($dir . $filename)) { // If file exists, add a number to it.
	        $filename = $prefix . '[' .++$i . ']' . $ext;
	    }
	    return $filename;
	}
	
	private function recursiveCopy($source, $destination) { 
		$source = rtrim($source, '/');
		$destination = rtrim($destination, '/');
		if(!file_exists($source) || !is_dir($source)) {
			return;
		}
		
		$directory = opendir($source); 
		
		@mkdir($destination, 0777, true); 
		
		while (false !== ($file = readdir($directory))) {
			if (($file != '.') && ($file != '..')) { 
				if (is_dir($source . '/' . $file)) { 
					self::recursiveCopy($source . '/' . $file, $destination . '/' . $file); 
				} else { 
					copy($source . '/' . $file, $destination . '/' . $file); 
				} 
			} 
		} 
		
		closedir($directory); 
	} 
	
    public static function unlink($dir, $deleteRootToo=true) {
		$dir = rtrim($dir, '/');
	    if(!$dh = @opendir($dir)) {
	        return;
	    }

    	while (false !== ($obj = readdir($dh))) {
	        if($obj == '.' || $obj == '..') {
	            continue;
	        }
	        if (!@unlink($dir . '/' . $obj)) {
	            self::unlink($dir.'/'.$obj, true);
	        }
    	}
    	closedir($dh);
	    if ($deleteRootToo) {
	        @rmdir($dir);
	    }

	    return;
	}
	
}

?>