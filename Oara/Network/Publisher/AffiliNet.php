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
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_An
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AffiliNet extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_client = null;
	/**
	 * Soap token.
	 */
	private $_token = null;
	/*
	 * User
	 */
	private $_user = null;
	/*
	 * User
	 */
	private $_password = null;

	/*
	 * PaymentHistory
	 */
	private $_paymentHistory = null;

	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array('ProgramId'		 => 'cid',
		'ProgramTitle'	 => 'name',
		'Url'			 => 'url',
		'Description'	 => 'description'
	);
	/**
	 * Converter configuration for the transactions.
	 * @var array
	 */
	private $_transactionConverterConfiguration = Array('TransactionStatus'		 => 'status',
		'TransactionId'			 => 'unique_id',
		'PublisherCommission'	 => 'commission',
		'NetPrice'				 => 'amount',
		'RegistrationDate'		 => 'date',
		'ProgramId'				 => 'merchantId',
		'SubId'					 => 'custom_id'
	);

	/**
	 * Converter configuration for the transactions for Payments.
	 * @var array
	 */
	private $_transactionPaymentsConverterConfiguration = Array('TransactionStatus'		 => 'status',
		'TransactionId'			 => 'unique_id',
		'PublisherCommission'	 => 'commission',
		'NetPrice'				 => 'amount',
		'CheckDate'				 => 'date',
		'ProgramId'				 => 'merchantId',
		'SubId'					 => 'custom_id'
	);

	/**
	 * Constructor.
	 * @param $affilinet
	 * @return Oara_Network_Publisher_An_Api
	 */
	public function __construct($credentials) {
		$this->_user = $credentials['user'];
		$this->_password = $credentials['password'];

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		self::Login();
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		//Set the webservice
		$publisherProgramServiceUrl = 'https://api.affili.net/V2.0/PublisherProgram.svc?wsdl';
		$publisherProgramService = new Oara_Import_Soap_Client($publisherProgramServiceUrl, array('compression'	 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
			'soap_version'	 => SOAP_1_1));
		//Call the function
		$params = Array('Query' => '');
		$merchantList = self::affilinetCall('merchant', $publisherProgramService, $params);

		if ($merchantList->TotalRecords > 0) {
			if ($merchantList->TotalRecords == 1) {
				$merchant = $merchantList->Programs->ProgramSummary;
				$merchantList = array();
				$merchantList[] = $merchant;
				$merchantList = Oara_Utilities::soapConverter($merchantList, $this->_merchantConverterConfiguration);
			} else {
				$merchantList = $merchantList->Programs->ProgramSummary;
				$merchantList = Oara_Utilities::soapConverter($merchantList, $this->_merchantConverterConfiguration);
			}
		} else {
			$merchantList = array();
		}

		return $merchantList;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		//Set the webservice
		$publisherStatisticsServiceUrl = 'https://api.affili.net/V2.0/PublisherStatistics.svc?wsdl';
		$publisherStatisticsService = new Oara_Import_Soap_Client($publisherStatisticsServiceUrl, array('compression'	 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
			'soap_version'	 => SOAP_1_1));
		$iterationNumber = self::calculeIterationNumber(count($merchantList), 100);

		for ($currentIteration = 0; $currentIteration < $iterationNumber; $currentIteration++) {
			$merchantListSlice = array_slice($merchantList, 100 * $currentIteration, 100);
			$merchantListAux = array();
			foreach ($merchantListSlice as $merchant) {
				$merchantListAux[] = (string) $merchant;
			}
			

			//Call the function
			$params = array(
				'StartDate'			 => strtotime($dStartDate->toString("yyyy-MM-dd")),
				'EndDate'			 => strtotime($dEndDate->toString("yyyy-MM-dd")),
				'TransactionStatus'	 => 'All',
				'ProgramIds'		 => $merchantListAux,
				/*
				 'SubId' => '',
				 'ProgramTypes' => 'All',
				 'MaximumRecords' => '0',
				 'ValuationType' => 'DateOfRegistration'
				 */
			);
			$currentPage = 1;
			$transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);

			while (isset($transactionList->TotalRecords) && $transactionList->TotalRecords > 0 && isset($transactionList->TransactionCollection->Transaction)) {
				$transactionCollection = array();
				if (!is_array($transactionList->TransactionCollection->Transaction)) {
					$transactionCollection[] = $transactionList->TransactionCollection->Transaction;
				} else {
					$transactionCollection = $transactionList->TransactionCollection->Transaction;
				}

				$transactionList = Oara_Utilities::soapConverter($transactionCollection, $this->_transactionConverterConfiguration);

				foreach ($transactionList as $transaction) {
					//$transaction['merchantId'] = 3901;
					$tDate = new Zend_Date($transaction["date"],"yyyy-MM-ddTHH:mm:ss");
					$transaction["date"] = $tDate->toString("yyyy-MM-dd HH:mm:ss");
					if ($transaction['status'] == 'Confirmed') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
						if ($transaction['status'] == 'Open') {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						} else
							if ($transaction['status'] == 'Cancelled') {
								$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
							}
					$totalTransactions[] = $transaction;
				}
				$currentPage++;
				$transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);
			}
		}

		return $totalTransactions;
	}
	
	/**
	 * Log in the API and get the data.
	 */
	public function Login() {
		$wsdlUrl = 'https://api.affili.net/V2.0/Logon.svc?wsdl';

		//Setting the client.
		$this->_client = new Oara_Import_Soap_Client($wsdlUrl, array('compression'	 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
			'soap_version'	 => SOAP_1_1));
		$demoPublisherId = 403233; // one of the publisher IDs of our demo database
		$developerSettings = array('SandboxPublisherID' => $demoPublisherId);
		$this->_token = $this->_client->Logon(array(
			'Username'		 => $this->_user,
			'Password'		 => $this->_password,
			'WebServiceType' => 'Publisher',
			//'DeveloperSettings' => $developerSettings
		));
		//echo "The token ". $this->_token ." expires:".$this->_client->GetIdentifierExpiration($this->_token)."\n\n";
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */

	public function getPaymentHistory() {
		$paymentHistory = array();
		//Set the webservice

		//At first, we need to be sure that there are some data.
		$auxStartDate = new Zend_Date("01-01-1990", "dd-MM-yyyy");
		$auxStartDate->setHour("00");
		$auxStartDate->setMinute("00");
		$auxStartDate->setSecond("00");
		$auxEndDate = new Zend_Date();
		$params = array(
			'CredentialToken'	 => $this->_token,
			'PublisherId'		 => $this->_user,
			'StartDate'			 => strtotime($auxStartDate->toString("yyyy-MM-dd")),
			'EndDate'			 => strtotime($auxEndDate->toString("yyyy-MM-dd")),
		);
		$accountServiceUrl = 'https://api.affili.net/V2.0/AccountService.svc?wsdl';
		$accountService = new Oara_Import_Soap_Client($accountServiceUrl, array('compression'	 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
			'soap_version'	 => SOAP_1_1));

		$paymentList = self::affilinetCall('payment', $accountService, $params);

		if (isset($paymentList->PaymentInformationCollection) && !is_array($paymentList->PaymentInformationCollection)) {
			$paymentList->PaymentInformationCollection = array($paymentList->PaymentInformationCollection);
		}
		if (isset($paymentList->PaymentInformationCollection)) {
			foreach ($paymentList->PaymentInformationCollection as $payment) {
				$obj = array();
				$obj['method'] = $payment->PaymentType;
				$obj['pid'] = $payment->PaymentId;
				$obj['value'] = $payment->GrossTotal;
				$obj['date'] = $payment->PaymentDate;
				$paymentHistory[] = $obj;
			}
		}
		$this->_paymentHistory = $paymentHistory;
		return $paymentHistory;
	}

	/**
	 * Call to the API controlling the exception and Login
	 */
	private function affilinetCall($call, $ws, $params, $try = 0, $currentPage = 0) {
		$result = null;
		try {

			switch ($call) {
			case 'merchant':
				$result = $ws->GetMyPrograms(array('CredentialToken'			 => $this->_token,
					'GetProgramsRequestMessage'	 => $params));
				break;
			case 'transaction':
				$pageSettings = array("CurrentPage" => $currentPage, "PageSize" => 100);
				$result = $ws->GetTransactions(array('CredentialToken'	 => $this->_token,
					'TransactionQuery'	 => $params,
					'PageSettings'		 => $pageSettings));
				break;
			case 'overview':
				$result = $ws->GetDailyStatistics(array('CredentialToken'					 => $this->_token,
					'GetDailyStatisticsRequestMessage'	 => $params));
				break;
			case 'payment':
				$result = $ws->GetPayments($params);
				break;
			default:
				throw new Exception('No Affilinet Call available');
				break;
			}
		} catch (Exception $e) {
			//checking if the token is valid
			if (preg_match("/Login failed/", $e->getMessage()) && $try < 5) {
				self::Login();
				$try++;
				$result = self::affilinetCall($call, $ws, $params, $try, $currentPage);
			} else {
				throw new Exception("problem with Affilinet API, no login fault");
			}
		}

		return $result;

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
