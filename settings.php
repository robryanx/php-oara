<?php

// Use composer autoload to load OARA classes as well as Zend Framework (dependency)
require_once 'vendor/autoload.php';

//Upgrade the memory limit
ini_set('memory_limit', '256M');
// Error handle configuration
error_reporting(E_ALL | E_STRICT);
//Defining the Global variables
define('BI_PATH_BASE', rtrim(realpath(dirname(__FILE__)), DIRECTORY_SEPARATOR));
define('DS', DIRECTORY_SEPARATOR);

define('COOKIES_BASE_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-oara-cookies');

//set up default timezone
date_default_timezone_set('GMT');

umask(0002);

//Adding the credentials ini into the Zend Registry
$config = new Zend_Config_Ini(BI_PATH_BASE.DS.'examples/credentials.ini', 'production');
Zend_Registry::getInstance()->set('credentialsIni', $config);
