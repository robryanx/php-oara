<?php
/**
 * Test Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Factory
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Test{
	/**
	 * Test the network provided
	 * @param $affiliateNetwork
	 * @return Oara_Factory_Interface
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
		if ($network->checkConnection()){
			//Get all the payments for this network.
			$paymentsList = $network->getPaymentHistory();

			echo "Number of payments: ".count($paymentsList)."\n\n";

			//Get all the Merhcants
			$merchantList = $network->getMerchantList(array());

			echo "Number of merchants: ".count($merchantList)."\n\n";

			// Building the array of merchant Id we want to retrieve data from.
			$merchantIdList = array();
			foreach ($merchantList as $merchant){
				$merchantIdList[] = $merchant['cid'];
			}
			//If we have joined any merchant
			if (!empty($merchantIdList)){
				//Split the dates monthly, Most of the network don't allow us to retrieve more than a month data
				$dateArray = Oara_Utilities::monthsOfDifference($startDate, $endDate);

				for ($i = 0; $i < count($dateArray); $i++){
					// Calculating the start and end date for the current month
					$monthStartDate = clone $dateArray[$i];
					$monthEndDate = null;

					if($i != count($dateArray)-1){
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

					echo "\n importing from ".$monthStartDate->toString("dd-MM-yyyy HH:mm:ss"). " to ". $monthEndDate->toString("dd-MM-yyyy HH:mm:ss") ."\n";

					$transactionList = $network->getTransactionList($merchantIdList, $monthStartDate, $monthEndDate);

					echo "Number of transactions: ".count($transactionList)."\n\n";

					$overviewList = $network->getOverviewList($transactionList, $merchantIdList, $monthStartDate, $monthEndDate);
						
					echo "Number register on the overview: ".count($overviewList)."\n\n";

				}


			}

			echo "Import finished \n\n";

		} else {
			echo "Error connecting to the network, check credentials\n\n";
		}
	}

}