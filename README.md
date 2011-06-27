php-oara
=============

The goal of the Open Affiliate Report Aggregator (OARA) is to develop
a set of PHP classes that can download affiliate reports from a number
of affiliate networks, and store the data in a common format.

We provide a simple structure and make it easy to add new networks using
the tools available.

This project is being used as part of [AffJet](http://www.affjet.com/),
which offers a hosted Affiliate Aggregator service, with a web interface 
and additional analysis tools. 

Development is sponsored by [AffJet](http://www.affjet.com) but welcome 
code contributions from anyone. 

Networks Supported
-------

The list of supported networks so far is:


* [Affiliate Window](http://www.affiliatewindow.com/) 
* [Affiliate Future](http://www.affiliatefuture.com/)
* [Trade Doubler](http://www.tradedoubler.com/)
* [Google AdSense](https://www.google.com/accounts/ServiceLogin?service=adsense)
* [AffiliNet](http://www.affili.net/en/Homepage.aspx)
* [Buy At](http://users.buy.at/)
* [CarTrawler](http://www.cartrawler.com/about/partners.php)
* [Commission Junction](http://www.cj.com/)
* [DGM](http://www.dgmpro.com/)
* [LinkShare](http://www.linkshare.com/)
* [Omg](http://uk.omgpm.com/)
* [PaidOnResults](http://www.paidonresults.com/)
* [SilverTap](http://www.silvertap.com/)
* [TerraVision](http://booking.terravision.eu/areap.asp?lng=EN)
* [Trade Tracker](http://www.tradetracker.com/gb/publisher/login)
* [Travel Jigsaw](http://www.traveljigsawgroup.com/affiliates/AffiliateLogin.do)
* [Web Gains](http://www.webgains.com/index.html)
* [Wow Trk](http://www.wowtrk.com/)
* [Zanox](http://www.zanox.com)

We are planning to add more, this is the to-do list:

* ClickBank
* Amazon
* Google Adwords
* Clixgalore
* More Niche
* Revenue Wire
* Shopzilla
* Insurance4carhire
* IC Breakdown
* Solution.weborama.com
* Mysavingsmedia
* Logical
* Escalate
* Skimlinks
* Impact
* Pepperjam
* Netaffiliation
* Publicidees.com
* Effiliation.com

System Requirements
-------------------

To run php-oara you will need to use PHP 5.3, and enable the CURL extension in your php.ini.

Also you will need to have GIT installed in your computer.

Getting Started
-----------

Once you have finished these steps you will be able to run the examples
for the different networks.

### Follow the steps

	1. Create the folder with the clone of the code.
	
	git clone git://github.com/paulmaunders/php-oara.git php-oara
	
	2. Change the directory to the root of the project
	
	cd php-oara
	
	3. Initialise the submodules (ZendFramework)
	
	git submodule init
	
	4. Update the submodules (ZendFramework)
	
	git submodule update
	
	5. Credentials.ini.sample
	
	In the example folder a "credentials.ini.sample" has been provided. 
	Please rename it to "credentials.ini" and follow the intructions
	in order to fill your crendentials.






Contributing
------------

If you want to contribute, you are welcome, please follow the next steps:


### Create your own fork

1. Follow the next [instructions](http://help.github.com/fork-a-repo/) to fork your own copy of php-oara.
Please read it carefully, as you can also follow the main branch in order to request the last changes in the code.

2. Work on your own repository.
Once all the code is in place you are free to add as many networks and improve the code as much as you can.

3. Send a pull request [instructions](http://help.github.com/send-pull-requests/)
When you think that your code is finished, send us a pull request and we will do the rest!


### Follow the structure

We would like you to follow the structure provided.  If you want to add a network,
please pay attention to the next rules:

* Create a class in the Oara/Network folder with the name of the network. This class must implement the Oara_Network Interface

* Implement the methods needed:
	* checkConnection
	* getMerchantList
	* getTransactionList
	* getOverviewList
	* getPaymentHistory
	
* Add the credentials to the credentials.ini.sample. (Please add also information about how to find your credentials)

* Add the generic example to the examples folder.


Network 
------------

The network classes must implement the Oara_Network interface, which includes these methods.

### checkConnection()
It checks if we are succesfully connected to the network

return boolean (true is connected successfully)

### getMerchantList($merchantMap)
Get the merchants joined for the network

* @param array $merchantMap - An array with the merchants already joined (Array of merchants unique ids), empty array by default

* return Array ( Array of Merchants )

### getTransactionList($merchantList, Zend_Date $dStartDate, Zend_Date $dEndDate)
Get the transactions for the network, from the "dStartDate" until "dEndDate" for the merchants provided

* @param array $merchantList - array with the merchants unique id we want to retrieve the data from

* @param Zend_Date $dStartDate - start date (included)

* @param Zend_Date $dEndDate - end date (included)

* return Array ( Array of Transactions )

### getOverviewList($transactionList, $merchantList, Zend_Date $dStartDate, Zend_Date $dEndDate)
Get the overview (a daily summary  of our statistics) for the network and the merchants selected for the date given

* @param array $transactionList - Transaction List

* @param array $merchantList - array with the merchants we want to retrieve the data from

* @param Zend_Date $dStartDate - start date (included)

* @param Zend_Date $dEndDate - end date (included)

* return Array ( Array of Overview )

### getPaymentHistory()
Get the Payments already done for this network

* return Array ( Array of Payments )

Merchant 
------------

It's an array with the following keys:

* name (not null) - Merchant's name 

* cid (not null) - Merchant's unique id 

* description - Merchant's description 

* url - Merchant's url 

Transaction 
------------

It's an array with the following keys:

* merchantId (not null) - Merchant's unique id

* date (not null) - Transaction date format, "2011-06-26 18:10:10"

* amount (not null) - Tranasction value  (double)

* commission (not null) - Transaction commission (double)

* status (not null) - Three different statuses :
	* Oara_Utilities::STATUS_CONFIRMED
	* Oara_Utilities::STATUS_PENDING
	* Oara_Utilities::STATUS_DECLINED
	
* link - Link where the transaction comes from

* website - Website where the transaction comes from

Overview 
------------

It's an array with the following keys:

* merchantId (not null) - Merchant's unique id

* date (not null) - Transaction date format, "2011-06-26 18:10:10"

* click_number (not null) - The number (int) of clicks for this date for this merchant, link and website 

* impression_number (not null) - The number (int) of impressions for this date for this merchant, link and website 

* transaction_number (not null) - The number (int) of transactions for this date for this merchant, link and website 

* transaction_confirmed_value (not null) -  Transaction value  (double) with status confirmed 

* transaction_confirmed_commission (not null) -  Transaction commission  (double) with status confirmed 

* transaction_pending_value (not null) - Transaction value  (double) with status pending 

* transaction_pending_commission (not null) -  Transaction commission  (double) with status pending 

* transaction_declined_value (not null) -  Transaction value  (double) with status declined 

* transaction_declined_commission (not null) -  Transaction commission  (double) with status declined 

* link - Link where the transaction comes from

* website - Website where the transaction comes from


Payment 
------------

It's an array with the next keys:

* pid (not null) - Payment's unique id

* date (not null) - Payment date format, "2011-06-26 18:10:10"

* value (not null) - Payment value

* method (not null) - Payment method (BACS, CHEQUE, ...)


Contact
------------

If you have any question, go to the project's [website](http://php-oara.affjet.com/) or
send an email to carlos@affjet.com
	

