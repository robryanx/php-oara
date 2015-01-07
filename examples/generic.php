<?php
/**
   The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set 
   of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
   
    Copyright (C) 2014  Fubra Limited
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
require realpath(dirname(__FILE__)).'/../settings.php';

/**
 * Network Name, please choose the Network from this list
 *
 * Be sure you have your credential on the credentials.ini.sample before runnig it
 *
 * FOR PUBLISHERS
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
 * DirectTrack
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
 *
 * FOR ADVERTISERS (be sure of changing the "type" param to "Advertiser")
 * CommissionJunction
 * ShareASale
 */
$networkName = "AffiliateWindow"; //Ex: AffiliateWindow
//Retrieving the credentials for the network
$config = Zend_Registry::getInstance()->get('credentialsIni');

$configName = strtolower($networkName);
$credentials = $config->$configName->toArray();

//Path for the cookie located inside of COOKIES_BASE_DIR
$credentials["cookiesDir"] = "example";
$credentials["cookiesSubDir"] = $networkName;
$credentials["cookieName"] = "test";

//The name of the network, It should be the same that the class inside Oara/Network
$credentials['networkName'] = $networkName;
//Which point of view "Publisher" or "Advertiser"
$credentials['type'] = "Publisher";
//The Factory creates the object
$network = Oara_Factory::createInstance($credentials);
Oara_Test::testNetwork($network);
