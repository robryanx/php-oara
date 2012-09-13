<?php
//Upgrade the memory limit
ini_set('memory_limit', '256M');
// Error handle configuration
error_reporting(E_ALL | E_STRICT);
//Defining the Global variables
define('BI_PATH_BASE', rtrim(realpath(dirname(__FILE__)), '/'));
define('DS', DIRECTORY_SEPARATOR);

//set up default timezone
date_default_timezone_set('GMT');

umask(0002);
//Defining the paths
$paths[] = BI_PATH_BASE.DS.'vendor/ZendFramework/library';
$paths[] = BI_PATH_BASE;
set_include_path(implode(PATH_SEPARATOR, $paths));

// Setting up Zend Auto Loader
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

//Adding the credentials ini into the Zend Registry
$config = new Zend_Config_Ini(BI_PATH_BASE.DS.'examples/credentials.ini', 'production');
Zend_Registry::getInstance()->set('credentialsIni', $config);
