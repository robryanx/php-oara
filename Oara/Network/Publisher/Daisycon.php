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

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Daisycon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Daisycon extends Oara_Network {

	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;

	private $_credentials = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		$user = $credentials['user'];
		$password = $credentials['password'];

		$sWsdl = "http://api.daisycon.com/publisher/soap/program/wsdl/";
		$aOptions = array(
			'login'		 => $user,
			'password'	 => md5($password),
			'features'	 => SOAP_SINGLE_ELEMENT_ARRAYS,
			'encoding'	 => 'utf-8',
			'trace'		 => 1,
		);
		$this->_client = new SoapClient($sWsdl, $aOptions);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$aFilter = array(
			'limitCount' => 1,
		);

		try {
			$mResult = $this->_client->getSubscriptions($aFilter);
		} catch (Exception $e) {
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
		$merchantList = array();

		$aFilter = array(
			'offset'	 => 0,
			'limitCount' => 100,
		);
		$mResult = $this->_client->getSubscriptions($aFilter);
		foreach ($mResult["return"] as $merchant) {
			
			$media = current($merchant->media);
			if ($media->status == 'approved'){
				$merchantList[$merchant->program_id] = $merchant->program_id;
			}
		}
		$resposeInfo = $mResult["responseInfo"];
		$numberIterations = self::calculeIterationNumber($resposeInfo->totalResults, 100);

		for ($i = 1; $i < ($numberIterations - 1); $i++) {

			$aFilter = array(
				'offset' => $i * 100,
				'limit'	 => 100,
			);
			$mResult = $this->_client->getSubscriptions($aFilter);
			foreach ($mResult["return"] as $merchant) {
				$media = current($merchant->media);
				if ($media->status == 'approved'){
					$merchantList[$merchant->program_id] = $merchant->program_id;
				}
			}
		}
		if (isset($merchantList[6389])){
			unset($merchantList[6389]);
		}
		
		sort($merchantList);
		$i = 0;
		while ($slice = array_slice($merchantList, $i * 100, 100)) {
			if (count($slice) > 0) {
				$aFilter = array(
					'program_id' => $slice
				);
				$mResult = $this->_client->getPrograms($aFilter);

				
				foreach ($mResult["return"] as $merchant) {
					$obj = Array();
					$obj['cid'] = $merchant->program_id;
					$obj['name'] = $merchant->name;
					$merchants[] = $obj;
				}
			}

			$i++;
		}

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$sWsdl = "http://api.daisycon.com/publisher/soap/transaction/wsdl/";
		$aOptions = array(
			'login'		 => $this->_credentials["user"],
			'password'	 => md5($this->_credentials["password"]),
			'features'	 => SOAP_SINGLE_ELEMENT_ARRAYS,
			'encoding'	 => 'utf-8',
			'trace'		 => 1,
		);
		$this->_client = new SoapClient($sWsdl, $aOptions);

		$aFilter = array(
			'offset'			 => 0,
			'limitCount'		 => 1,
			'program_ids'		 => $merchantList,
			'selection_start'	 => $dStartDate->toString("yyyy-MM-dd"),
			'selection_end'		 => $dEndDate->toString("yyyy-MM-dd")
		);
		$mResult = $this->_client->getTransactions($aFilter);
		$resposeInfo = $mResult["responseInfo"];
		$numberIterations = self::calculeIterationNumber($resposeInfo->totalResults, 1000);

		for ($i = 0; $i < $numberIterations; $i++) {
			$aFilter = array('offset'			 => $i * 1000,
				'limitCount'		 => 1000,
				'program_ids'		 => $merchantList,
				'selection_start'	 => $dStartDate->toString("yyyy-MM-dd"),
				'selection_end'		 => $dEndDate->toString("yyyy-MM-dd")
			);

			$mResult = $this->_client->getTransactions($aFilter);
			foreach ($mResult["return"] as $transactionObject) {
				$merchantId = $transactionObject->program_id;
				if (in_array($merchantId, $merchantList)) {

					$transaction = Array();
					$transaction['unique_id'] = $transactionObject->affiliatemarketing_id;

					$transaction['merchantId'] = $merchantId;
					$transactionDate = new Zend_Date($transactionObject->date_transaction, 'dd-MM-yyyyTHH:mm:ss');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

					if ($transactionObject->sub_id != null) {
						$transaction['custom_id'] = $transactionObject->sub_id;
					}
					if ($transactionObject->status == 'approved') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
						if ($transactionObject->status == 'pending' || $transactionObject->status == 'potential' || $transactionObject->status == 'open') {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						} else
							if ($transactionObject->status == 'disapproved' || $transactionObject->status == 'incasso') {
								$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
							} else {
								throw new Exception("New status {$transactionObject->status}");
							}
					$transaction['amount'] = Oara_Utilities::parseDouble($transactionObject->revenue);
					$transaction['currency'] = $transactionObject->currency;
					$transaction['commission'] = Oara_Utilities::parseDouble($transactionObject->commision);
					$totalTransactions[] = $transaction;
				}
			}
		}

		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();
		return $paymentHistory;
	}

	/**
	 * Calculate the number of iterations needed
	 * @param $rowAvailable
	 * @param $rowsReturned
	 */
	private function calculeIterationNumber($rowAvailable, $rowsReturned) {
		$iterationDouble = (double) ($rowAvailable / $rowsReturned);
		$iterationInt = (int) ($rowAvailable / $rowsReturned);
		if ($iterationDouble > $iterationInt) {
			$iterationInt++;
		}
		return $iterationInt;
	}
}
