<?php
require realpath(dirname(__FILE__)).'/../settings.php';

/**
 * Network Name, please choose the Network from this list
 * 
 * Be sure you have your credential on the credentials.ini.sample before runnig it
 * 
 * 
 * Adsense
 * AffiliateFuture
 * AffiliatesUnited
 * AffiliateWindow
 * AffiliNet
 * Amazon
 * AutoEurope
 * Bet365
 * BuyAt
 * CarTrawler
 * ClickBank
 * ClixGalore
 * CommissionJunction
 * Daisycon
 * Demo
 * Dgm
 * Ebay
 * Effiliation
 * GoogleCheckout
 * HolidayAutos
 * Ladbrokers
 * LinkShare
 * M4n
 * NetAffiliation
 * Omg
 * PaidOnResults
 * PayMode
 * Publicidees
 * SilverTap
 * Skimlinks
 * Smg
 * Stream20
 * TerraVision
 * TradeDoubler
 * TradeTracker
 * TravelJigsaw
 * WebGains
 * WowTrk
 * Zanox
 */
$networkName = "ChooseYourNetwork"; //Ex: AffiliateWindow
//Retrieving the credentials for AffiliateFuture
$config = Zend_Registry::getInstance()->get('credentialsIni');

$configName = strtolower($networkName);
$credentials = $config->$configName->toArray();



//Path for the cookie located inside the Oara/data/curl folder
$credentials["cookiesDir"] = "example";
$credentials["cookiesSubDir"] = $networkName;
$credentials["cookieName"] = "test";

//The name of the network, It should be the same that the class inside Oara/Network
$credentials['networkName'] = $networkName;
//The Factory creates the object
$network = Oara_Factory::createInstance($credentials);
Oara_Test::testNetwork($network);
