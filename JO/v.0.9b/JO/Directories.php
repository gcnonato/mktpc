<?php

class JO_Directories extends DirectoryIterator {

	public static function deleteOldFiles($directory) {
	    $filenames = array();
	    $iterator = new JO_Directories($directory);
		
	    foreach($iterator as $fileinfo) {
	        if ($fileinfo->isFile() && ((time() - $fileinfo->getMTime()) > (7*24*60*60))) {
	            unlink($fileinfo->getPathname());
	        } elseif($fileinfo->isDir() && !in_array($fileinfo->getBasename(), array('.', '..'))) {
	        	self::deleteOldFiles($directory .'/'. $fileinfo->getBasename());
	        }
	    }
	}
}

?>