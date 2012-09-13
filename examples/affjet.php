#!/usr/bin/php
<?php
/**
 * Affjet Cli, you can send different options via console and it will give you the output required.
 * Please check you have entered your crendentials in your credential.ini before you run this script
 *
 * Parameters:
 *
 * 	-s 	startDate with format dd/MM/yyyy (11/06/2011)
 *  -e 	endDate with format dd/MM/yyyy (11/06/2011)
 *	-n 	network name of the Oara_Network class for the network (AffiliateWindow, BuyAt, Dgm, WebGains......)
 *	-t 	type this param is not compulsory, choose which report we want, by default it will show us all of them (payment, merchant, transaction, overview)
 *
 *
 *	Examples from command line:
 *
 *	php affjet.php -s 12/02/2010 -e 15/06/2011 -n TradeDoubler
 *	php affjet.php -s 12/02/2010 -e 15/06/2011 -n TradeDoubler -t merchant
 *	php affjet.php -s 12/02/2010 -e 15/06/2011 -n AffiliateWindow -t payment
 *
 */

require realpath(dirname(__FILE__)).'/../settings.php';

$arguments = Oara_Utilities::arguments($argv);
$argumentsMap = array();

$argumentsNumber = count($arguments['arguments']);
for ($i = 0; $i < $argumentsNumber; $i++) {

	$argumentsMap[$arguments['flags'][$i]] = $arguments['arguments'][$i];
}

if (isset($argumentsMap['s']) && isset($argumentsMap['e']) && isset($argumentsMap['n'])) {
	//Retrieving the credentials for the network selected
	$config = Zend_Registry::getInstance()->get('credentialsIni');
	$iniNetworkOption = strtolower($argumentsMap['n']);
	$credentials = $config->$iniNetworkOption->toArray();

	//Path for the cookie located inside the Oara/data/curl folder
	$credentials["cookiesDir"] = "example";
	$credentials["cookiesSubDir"] = "Affjet";
	$credentials["cookieName"] = "test";

	//The name of the network, It should be the same that the class inside Oara/Network
	$credentials['networkName'] = $argumentsMap['n'];
	//The Factory creates the object
	$network = Oara_Factory::createInstance($credentials);

	Oara_Test::affjetCli($argumentsMap, $network);
} else {
	fwrite(STDERR, "Usage: affjet [-s startDate] [-e endDate] [-t type] [-n network]\n"."\n"." 	NB: Please check you have entered your credentials in your credential.ini before you run this script."."\n"." 	Parameters:\n"."\n"." 		-s 	startDate with format dd/MM/yyyy (11/06/2011)\n"." 		-e 	endDate with format dd/MM/yyyy (11/06/2011)\n"."		-n 	network name of the Oara_Network class for the network (AffiliateWindow, BuyAt, Dgm, WebGains......)\n"."		-t 	type this param is not compulsory, choose which report we want, by default it will show us all of them (payment, merchant, transaction, overview)\n"."\n"."	Examples from command line:\n"."\n"."		php affjet.php -s 12/02/2010 -e 15/06/2011 -n TradeDoubler\n"."		php affjet.php -s 12/02/2010 -e 15/06/2011 -n TradeDoubler -t merchant\n"."		php affjet.php -s 12/02/2010 -e 15/06/2011 -n AffiliateWindow -t payment\n");
}
