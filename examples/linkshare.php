<?php
require realpath(dirname(__FILE__)) . '/../settings.php';

//Retrieving the credentials for AffiliateFuture
$config = Zend_Registry::getInstance()->get('credentialsIni');
$credentials = $config->linkshare->toArray();

//Choosing the network, it could be uk or us
$credentials['network'] = 'uk';

//Path for the cookie located inside the Oara/data/curl folder
$credentials["cookiesDir"] = "example";
$credentials["cookiesSubDir"] = "LinkShare";
$credentials["cookieName"] = "test";

//The name of the network, It should be the same that the class inside Oara/Network
$credentials['networkName'] = 'LinkShare';
//The Factory creates the object
$network = Oara_Factory::createInstance($credentials);
Oara_Test::testNetwork($network);