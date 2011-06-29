<?php
/**
 * Affjet Cli, you can send different options via console and it will give you the output required.
 * Please check you have entered your crendentials in your credential.ini before you run this script
 *
 * Paramters:
 * 
 * 	--startDate -> with format dd/MM/yyyy (11/06/2011)
 *  --endDate -> with format dd/MM/yyyy (11/06/2011)
 *	--network -> name of the Oara_Network class for the network (AffiliateWindow, BuyAt, Dgm, WebGains......)
 *	--type -> this param is not compulsory, choose which report we want, by default it will show us all of them (payment, merchant, transaction, overview)
 *
 *
 *	Examples from command line:
 *
 *	php affjet.php --startDate=12/02/2010 --endDate=15/06/2011 --network=TradeDoubler
 *	php affjet.php --startDate=12/02/2010 --endDate=15/06/2011 --network=TradeDoubler --type=merchant
 *	php affjet.php --startDate=12/02/2010 --endDate=15/06/2011 --network=AffiliateWindow --type=payment
 *
 */



require realpath(dirname(__FILE__)) . '/../settings.php';

$arguments = Oara_Utilities::arguments($argv);
$argumentsMap = array();
foreach ($arguments['options'] as $option){
	$argumentsMap[$option[0]] = $option[1];
}

if (isset($argumentsMap['startDate']) && isset($argumentsMap['endDate']) && isset($argumentsMap['network'])){
	//Retrieving the credentials for the network selected
	$config = Zend_Registry::getInstance()->get('credentialsIni');
	$iniNetworkOption = strtolower($argumentsMap['network']);
	$credentials = $config->$iniNetworkOption->toArray();

	//Path for the cookie located inside the Oara/data/curl folder
	$credentials["cookiesDir"] = "example";
	$credentials["cookiesSubDir"] = "Affjet";
	$credentials["cookieName"] = "test";

	//The name of the network, It should be the same that the class inside Oara/Network
	$credentials['networkName'] = $argumentsMap['network'];
	//The Factory creates the object
	$network = Oara_Factory::createInstance($credentials);

	Oara_Test::affjetCli($argumentsMap, $network);
} else {
	echo "\n Parameters needed, please check the info in the examples/affjet.php file";

}


