<?php
namespace Oara\Network\Publisher;
include_once (dirname(__FILE__) . '/../settings.php');

function get_defined_classes ($path, $parent = null) {
    $classes = array();
    $files = scandir($path);
    foreach ($files as $file) {
        $filePath = $path . '/' . $file;
        if (!is_dir($filePath) && is_file($filePath) && substr($file, strlen($file) - 4, 4) == '.php') {
            $phpCode = file_get_contents($filePath);
            $tokens = token_get_all($phpCode);
            $count = count($tokens);
            for ($i = 2; $i < $count; $i++) {
                if (   $tokens[$i - 2][0] == T_CLASS
                    && $tokens[$i - 1][0] == T_WHITESPACE
                    && $tokens[$i][0] == T_STRING
                    && $tokens[1][0] == T_NAMESPACE
                    && $tokens[2][0] == T_WHITESPACE
                    && $tokens[3][0] == T_STRING && $tokens[3][1] == "Oara"
                    && $tokens[4][0] == T_NS_SEPARATOR
                    && $tokens[5][0] == T_STRING && $tokens[5][1] == "Network"
                    && $tokens[6][0] == T_NS_SEPARATOR
                    && $tokens[7][0] == T_STRING && $tokens[7][1] == "Publisher") {

                    $className = ($parent) ? $parent . '\\' . $tokens[$i][1] : $tokens[$i][1];
                    $classes[] = $className;
                }
            }
        }
    }
    return $classes;
}

function get_subdirectory_classes ($path, $parent = null) {
    $classes = get_defined_classes($path, $parent);
    $directories = scandir($path);
    foreach ($directories as $directory) {
        $dirPath = $path . '/' . $directory;
        if (is_dir($dirPath) && !is_file($dirPath) && $directory != '.' && $directory != '..') {
            $classes = array_merge($classes, get_subdirectory_classes($dirPath, $directory));
        }
    }
    return $classes;
}

$publisherPath = dirname(__FILE__) . '/../Oara/Network/Publisher';
$classesImported = get_subdirectory_classes($publisherPath);

$file = fopen(dirname(__FILE__) . '/../README.md', 'w');
fwrite($file, 'php-oara
=============

The goal of the Open Affiliate Report Aggregator (OARA) is to develop
a set of PHP classes that can download affiliate reports from a number
of affiliate networks, and store the data in a common format.

We provide a simple structure and make it easy to add new networks using
the tools available.

This project is being used as part of [AffJet](http://www.affjet.com/),
which offers a hosted Affiliate Aggregator service, with a web interface
and additional analysis tools.

Development is sponsored by [AffJet](http://www.affjet.com) but we welcome
code contributions from anyone.

License
-------
PHP-OARA is available under a dual license model; either AGPL or a commercial license, depending on your requirements. If you wish to use php-oara in an open source project, or one for internal use only then you can choose the AGPL. If you wish to use php-oara in a commercial project that will be available to external users, then you should contact us for a commercial license.

This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License (LICENSE.TXT).

If you are interested in being a contributor to this project we encourage to read, fill and send this file (FubraLimited-ContributorLicenseAgreement.docx) to support@fubra.com.

Networks Supported
-------

The list of supported networks for Publishers so far is:

');

foreach ($classesImported as $class) {
    fwrite($file, '* ' . $class . PHP_EOL);
}
fwrite($file, '

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

	git clone https://github.com/fubralimited/php-oara.git php-oara

	2. Change the directory to the root of the project

	cd php-oara

	3. Initialise composer

	curl -s https://getcomposer.org/installer | php --
	php composer.phar self-update
	php composer.phar install

	5. test.php

	In the examples folder a "test.php" has been provided.
	Instantiate a network (new \Oara\Network\Publisher\LinkShare\UK() for example), and set
	the needed credentials to login.



PHP OARA on Composer
-----------
You can use the package "fubralimited/php-oara" from composer instead to import the library.


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

* Create a class in the Oara/Network folder with the name of the network. This class must implement the \Oara\Network Interface

* Implement the methods needed:
    * login
    * getNeededCredentials
	* checkConnection
	* getMerchantList
	* getTransactionList
	* getPaymentHistory
	* paymentTransactions


Network
------------

The network classes must implement the \Oara\Network interface, which includes these methods.

### login(array $credentials)
Makes the login process with the credentials provided.

* @param array $credentials - array with credentials to login

### getNeededCredentials()
Returns an array with the required parameters to login.

return Array ( Array with required parameters)

### checkConnection()
It checks if we are succesfully connected to the network

return boolean (true is connected successfully)

### getMerchantList()
Gets the merchants joined for the network

* return Array ( Array of Merchants )

### getTransactionList(array $merchantList, \DateTime $dStartDate, \DateTime $dEndDate)
Gets the transactions for the network, from the "dStartDate" until "dEndDate" for the merchants provided

* @param array $merchantList - array with the merchants unique id we want to retrieve the data from

* @param \DateTime $dStartDate - start date (included)

* @param \DateTime $dEndDate - end date (included)

* @param array $merchantMap - array with the merchants indexed by name, only in case we can\'t get the merchant id in the transaction report, we may need to link it by name.

* return Array ( Array of Transactions )


### getPaymentHistory()
Gets the Payments already done for this network

* return Array ( Array of Payments )

### paymentTransactions($paymentId, $merchantList, $startDate)
Gets the Transactions Id for a paymentId

* @param array $paymentId - Payment Id of the payment we want the trasactions unique_id list.

* @param array $merchantList - array with the merchants we want to retrieve the data from

* @param \DateTime $startDate - start date ,it may be useful to filter the data in some networks

* return Array ( Array of Transcation unique_id )

Entities
------------

### Merchant

It\'s an array with the following keys:

* name (not null) - Merchant\'s name

* cid (not null) - Merchant\'s unique id

* description - Merchant\'s description

* url - Merchant\'s url

### Transaction

It\'s an array with the following keys:

* merchantId (not null) - Merchant\'s unique id

* date (not null) - Transaction date format, "2011-06-26 18:10:10"

* amount (not null) - Tranasction value  (double)

* commission (not null) - Transaction commission (double)

* status (not null) - Four different statuses:
	* \Oara\Utilities::STATUS_CONFIRMED
	* \Oara\Utilities::STATUS_PENDING
	* \Oara\Utilities::STATUS_DECLINED
	* \Oara\Utilities::STATUS_PAID

* unique_id - Unique id for the transaction (string)
* custom_id - Custom id (or sub id) for the transaction (string), custom param you put on your links to see the performance or who made the sale.


### Payment

It\'s an array with the next keys:

* pid (not null) - Payment\'s unique id

* date (not null) - Payment date format, "2011-06-26 18:10:10"

* value (not null) - Payment value

* method (not null) - Payment method (BACS, CHEQUE, ...)



Contact
------------

If you have any question, go to the project\'s [website](http://php-oara.affjet.com/) or
send an email to support@affjet.com


');
fclose($file);

