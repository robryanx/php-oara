<?php
/**
   The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set 
   of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
   
    Copyright (C) 2014  Carlos Morillo Merino
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	and we should add some contact information
**/	
/**
 * Test Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Factory
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Test {
	/**
	 * Test the network provided
	 * @param $affiliateNetwork
	 * @return none
	 */
	public static function testNetwork($network) {
		//Start date, the first two months ago
		$startDate = new Zend_Date();
		$startDate->setDay(1);
		$startDate->subMonth(2);
		$startDate->setHour(00);
		$startDate->setMinute(00);
		$startDate->setSecond(00);

		//Yesterday, some networks don't give us the data for the same day, then is the safer way to have our data
		$endDate = new Zend_Date();
		$endDate->subDay(1);
		$endDate->setHour(23);
		$endDate->setMinute(59);
		$endDate->setSecond(59);

		//are we connected?
		if ($network->checkConnection()) {
			//Get all the payments for this network.
			$paymentsList = $network->getPaymentHistory();

			echo "Total Number of payments: ".count($paymentsList)."\n\n";

			//Get all the Merhcants
			$merchantList = $network->getMerchantList();

			echo "Number of merchants: ".count($merchantList)."\n\n";

			// Building the array of merchant Id we want to retrieve data from.
			$merchantIdList = array();
			foreach ($merchantList as $merchant) {
				$merchantIdList[] = $merchant['cid'];
			}
			
			$merchantMap = array();
			foreach ($merchantList as $merchant){
				$merchantMap[$merchant['name']] = $merchant['cid'];
			}
			
			
			//If we have joined any merchant
			if (!empty($merchantIdList)) {
				//Split the dates monthly, Most of the network don't allow us to retrieve more than a month data
				$dateArray = Oara_Utilities::monthsOfDifference($startDate, $endDate);

				for ($i = 0; $i < count($dateArray); $i++) {
					// Calculating the start and end date for the current month
					$monthStartDate = clone $dateArray[$i];
					$monthEndDate = null;

					if ($i != count($dateArray) - 1) {
						$monthEndDate = clone $dateArray[$i];
						$monthEndDate->setDay(1);
						$monthEndDate->addMonth(1);
						$monthEndDate->subDay(1);
					} else {
						$monthEndDate = $endDate;
					}
					$monthEndDate->setHour(23);
					$monthEndDate->setMinute(59);
					$monthEndDate->setSecond(59);

					echo "\n importing from ".$monthStartDate->toString("dd-MM-yyyy HH:mm:ss")." to ".$monthEndDate->toString("dd-MM-yyyy HH:mm:ss")."\n";

					$transactionList = $network->getTransactionList($merchantIdList, $monthStartDate, $monthEndDate, $merchantMap);

					echo "Number of transactions: ".count($transactionList)."\n\n";

				}

			}

			echo "Import finished \n\n";

		} else {
			echo "Error connecting to the network, check credentials\n\n";
		}
	}

	/**
	 * The affjet cli , read the arguments and build the report requested
	 * @param array arguments, Map of the cli arguments
	 * * @param network, the affiliate network
	 * @return none
	 */
	public static function affjetCli($arguments, $network) {

		//Start date, the first two months ago
		$startDate = new Zend_Date($arguments['s'], "dd/MM/yyyy");
		$startDate->setHour(00);
		$startDate->setMinute(00);
		$startDate->setSecond(00);

		//Yesterday, some networks don't give us the data for the same day, then is the safer way to have our data
		$endDate = new Zend_Date($arguments['e'], "dd/MM/yyyy");
		$endDate->setHour(23);
		$endDate->setMinute(59);
		$endDate->setSecond(59);

		//are we connected?
		fwrite(STDERR, "\nAccessing to your account \n\n");
		if ($network->checkConnection()) {
			fwrite(STDERR, "Connected successfully \n\n");
			if (!isset($arguments['t']) || $arguments['t'] == 'payment') {

				fwrite(STDERR, "Getting payments, please wait \n\n");
				//Get all the payments for this network.
				$paymentsList = $network->getPaymentHistory();

				fwrite(STDERR, "Number of payments: ".count($paymentsList)."\n\n");
				fwrite(STDERR, "------------------------------------------------------------------------\n");
				fwrite(STDERR, "ID			DATE					VALUE\n");
				fwrite(STDERR, "------------------------------------------------------------------------\n");
				foreach ($paymentsList as $payment) {
					$paymentDate = new Zend_Date($payment['date'], "yyyy-MM-dd HH:mm:ss");
					if ($paymentDate->compare($startDate) >= 0 && $paymentDate->compare($endDate) <= 0) {
						fwrite(STDERR, $payment['pid']."			".$payment['date']."			".$payment['value']." \n");
					}

				}

				if (isset($arguments['t']) && $arguments['t'] == 'payment') {
					return null;
				}

			}

			//Get all the Merhcants
			fwrite(STDERR, "\nGetting merchants, please wait \n\n");
			$merchantList = $network->getMerchantList(array());

			if (!isset($arguments['t']) || $arguments['t'] == 'merchant') {

				fwrite(STDERR, "Number of merchants: ".count($merchantList)."\n\n");
				fwrite(STDERR, "--------------------------------------------------\n");
				fwrite(STDERR, "ID			NAME\n");
				fwrite(STDERR, "--------------------------------------------------\n");
				foreach ($merchantList as $merchant) {
					fwrite(STDERR, $merchant['cid']."			".$merchant['name']." \n");
				}

				if (isset($arguments['t']) && $arguments['t'] == 'merchant') {
					return null;
				}
			}

			// Building the array of merchant Id we want to retrieve data from.
			$merchantIdList = array();
			foreach ($merchantList as $merchant) {
				$merchantIdList[] = $merchant['cid'];
			}
			//If we have joined any merchant
			if (!empty($merchantIdList)) {
				$merchantMap = array();
				foreach ($merchantList as $merchant){
					$merchantMap[$merchant['name']] = $merchant['cid'];
				}
				//Split the dates monthly, Most of the network don't allow us to retrieve more than a month data
				$dateArray = Oara_Utilities::monthsOfDifference($startDate, $endDate);

				for ($i = 0; $i < count($dateArray); $i++) {
					// Calculating the start and end date for the current month
					$monthStartDate = clone $dateArray[$i];
					$monthEndDate = null;

					if ($i != count($dateArray) - 1) {
						$monthEndDate = clone $dateArray[$i];
						$monthEndDate->setDay(1);
						$monthEndDate->addMonth(1);
						$monthEndDate->subDay(1);
					} else {
						$monthEndDate = $endDate;
					}
					$monthEndDate->setHour(23);
					$monthEndDate->setMinute(59);
					$monthEndDate->setSecond(59);

					fwrite(STDERR, "\n*Importing data from ".$monthStartDate->toString("dd-MM-yyyy HH:mm:ss")." to ".$monthEndDate->toString("dd-MM-yyyy HH:mm:ss")."\n\n");
					fwrite(STDERR, "Getting transactions, please wait \n\n");
					$transactionList = $network->getTransactionList($merchantIdList, $monthStartDate, $monthEndDate, $merchantMap);

					if (!isset($arguments['t']) || $arguments['t'] == 'transaction') {
						fwrite(STDERR, "Number of transactions: ".count($transactionList)."\n\n");

						$totalAmount = 0;
						$totalCommission = 0;
						foreach ($transactionList as $transaction) {
							$totalAmount += $transaction['amount'];
							$totalCommission += $transaction['commission'];

						}
						fwrite(STDERR, "--------------------------------------------------\n");
						fwrite(STDERR, "TOTAL AMOUNT		$totalAmount\n");
						fwrite(STDERR, "TOTAL COMMISSION	$totalCommission\n");
						fwrite(STDERR, "--------------------------------------------------\n\n");

					}
				}

			}

			fwrite(STDERR, "Import finished \n\n");

		} else {
			fwrite(STDERR, "Error connecting to the network, check credentials \n\n");
		}
	}

}
