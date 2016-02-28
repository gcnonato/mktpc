<?php
//error_reporting(0);

// Define application path
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application/'));

// Define base path
defined('BASE_PATH')
    || define('BASE_PATH', realpath(dirname(__FILE__)));
    
// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../JO/v.0.9b/'),
    realpath(APPLICATION_PATH . '/library/'),
    get_include_path(),
)));

require_once 'JO/Application.php';

// Create application, bootstrap, and run
$application = new JO_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/config/application.ini'
); 

// Set Routers links
$configs_files = glob(APPLICATION_PATH . '/config/config_*.ini');
if($configs_files) {
	foreach($configs_files AS $file) {
		$config = new JO_Config_Ini($file);
		$application->setOptions($config->toArray());
		JO_Registry::set(basename($file, '.ini'), $config->toArray());
	}
}

// Set Routers links
$routers_files = glob(APPLICATION_PATH . '/config/routers/*.ini');
if($routers_files) {
	foreach($routers_files AS $file) {
		$config = new JO_Config_Ini($file, null, false, true);
		$application->setOptions($config->toArray());
		JO_Registry::set('routers_'.basename($file, '.ini'), $config->toArray());
	}
}

//dispatch application
$application->dispatch();