<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Belboon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Belboon extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_client = null;
	/**
	 * Platform list.
	 */
	private $_platformList = null;
	/*
	 * User
	 */
	private $_user = null;
	/*
	 * User
	 */
	private $_password = null;

	/**
	 * Constructor.
	 * @param $affilinet
	 * @return Oara_Network_Publisher_An_Api
	 */
	public function __construct($credentials) {
		$this->_user = $credentials['user'];
		$this->_password = $credentials['apiPassword'];

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
		$merchantList = array();
		foreach ($this->_platformList as $platform){
			$result = $this->_client->getPrograms($platform["id"], null, utf8_encode('PARTNERSHIP'), null, null,null, 0 );
			foreach ($result->handler->programs as $merchant){
				$obj = array();
				$obj["name"] = $merchant["programname"];
				$obj["cid"] = $merchant["programid"];
				$obj["url"] = $merchant["advertiserurl"];
				$merchantList[] = $obj;
			}


		}
			

		return $merchantList;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$result = $this->_client->getEventList(null, null, null, null, null, $dStartDate->toString("YYYY-MM-dd"), $dEndDate->toString("YYYY-MM-dd"), null, null, null, null, 0);


		foreach ($result->handler->events as $event) {
			if (in_array($event["programid"],$merchantList)){
				
			
				$transaction = Array();
				$transaction['unique_id'] = $event["eventid"];
				$transaction['merchantId'] = $event["programid"];
				$transaction['date'] = $event["eventdate"];

				if ($event["subid"] != null) {
					$transaction['custom_id'] = $event["subid"];
				}

				if ($event["eventstatus"] == 'APPROVED') {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if ($event["eventstatus"] == 'PENDING') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if ($event["eventstatus"] == 'REJECTED') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						}

				$transaction['amount'] = $event["netvalue"];

				$transaction['commission'] = $event["eventcommission"];
				$totalTransactions[] = $transaction;
			}
		}

		return $totalTransactions;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverview = Array();

		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);

		foreach ($transactionArray as $merchantId => $merchantTransaction) {
			foreach ($merchantTransaction as $date => $transactionList) {

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				$overview['click_number'] = 0;
				$overview['impression_number'] = 0;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission'] = 0;
				$overview['transaction_pending_value'] = 0;
				$overview['transaction_pending_commission'] = 0;
				$overview['transaction_declined_value'] = 0;
				$overview['transaction_declined_commission'] = 0;
				$overview['transaction_paid_value'] = 0;
				$overview['transaction_paid_commission'] = 0;
				foreach ($transactionList as $transaction) {
					$overview['transaction_number']++;
					if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED) {
						$overview['transaction_confirmed_value'] += $transaction['amount'];
						$overview['transaction_confirmed_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_PENDING) {
						$overview['transaction_pending_value'] += $transaction['amount'];
						$overview['transaction_pending_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED) {
						$overview['transaction_declined_value'] += $transaction['amount'];
						$overview['transaction_declined_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_PAID) {
						$overview['transaction_paid_value'] += $transaction['amount'];
						$overview['transaction_paid_commission'] += $transaction['commission'];
					}
				}
				$totalOverview[] = $overview;
			}
		}

		return $totalOverview;
	}
	/**
	 * Log in the API and get the data.
	 */
	public function Login() {
		//Setting the client.
		$this->_client = new SoapClient('http://api.belboon.com/?wsdl', array('login' => $this->_user, 'password' => $this->_password, 'trace' => true));
		$result = $this->_client->getAccountInfo();
		sleep(2);
		$oSmartFeed = new SoapClient("http://smartfeeds.belboon.com/SmartFeedServices.php?wsdl");
		
		$oSessionHash = $oSmartFeed->login($this->_user, $this->_password);

		if(!$oSessionHash->HasError){

			$sSessionHash = $oSessionHash->Records['sessionHash'];

			$aResult = $oSmartFeed->getPlatforms($sSessionHash);
			$platformList = array();
			foreach ($aResult->Records as $record){
				if ($record['status'] == "active"){
					$platformList[] = $record;
				}
			}
			$this->_platformList = $platformList;
		}


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
