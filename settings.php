<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
  
 Copyright (C) 2016  Fubra Limited
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.
 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
// Use composer autoload to load OARA classes as well as Zend Framework (dependency)
require_once 'vendor/autoload.php';

//Upgrade the memory limit
ini_set('memory_limit', '256M');
// Error handle configuration
error_reporting(E_ALL | E_STRICT);
//Defining the Global variables 
define('BI_PATH_BASE', rtrim(realpath(dirname(__FILE__)), DIRECTORY_SEPARATOR));
define('DS', DIRECTORY_SEPARATOR);
define('COOKIES_BASE_DIR', realpath ( dirname ( __FILE__ ) ) . DIRECTORY_SEPARATOR."Oara".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."curl");

//set up default timezone
date_default_timezone_set('GMT');
umask(0002);