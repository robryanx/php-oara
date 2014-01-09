<?php
require_once('DirectTrack/lib/nusoap.php');
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_DirectTrack
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_DirectTrack extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_apiClient = null;

	/**
	 * Client
	 */
	private $_client = null;
	/**
	 * Password
	 */
	private $_password = null;
	/**
	 * Publisher
	 */
	private $_publisherId = null;
	/**
	 * Params
	 */
	private $_params = null;

	/**
	 * Constructor.
	 * @param $affiliateWindow
	 * @return Oara_Network_Publisher_Aw_Api
	 */
	public function __construct($credentials) {
		ini_set('default_socket_timeout', '120');
		$this->_client = $credentials['client'];
		$this->_password = $credentials['password'];
		$this->_publisherId = $credentials['publisherId'];
		
		$params = array();
    	$params["client"] = $this->_client;
    	$params["add_code"] = $this->_publisherId;
    	$params["password"] = $this->_password;
    	$this->_params = $params;

		$wsdlUrl = 'http://secure.directtrack.com/api/soap_affiliate.php?wsdl';
		//Setting the client.
		$this->_apiClient = new nusoap_client($wsdlUrl, true);

		throw new Exception("DirectTrack has to be renew");
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		try {
			$params = $this->_params;
			$params["program_id"] = 1;
			$result = $this->_apiClient->call('campaignInfo', $params, 'http://soapinterop.org/', 'http://soapinterop.org/');
			if ($result["faultcode"] == "Invalid Login"){
				throw new Exception ("Invalid Login");
			}
			$connection = true;
		} catch (Exception $e) {

		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
	
		$params = $this->_params;
		$result = $this->_apiClient->call('campaignInfo', $params, 'http://soapinterop.org/', 'http://soapinterop.org/');
		if (!is_array($result)){
			$result = html_entity_decode($result);
			$xml = simplexml_load_string($result, null);
			
			if ($xml){
				foreach ($xml->program as $merchant) {
					$obj = Array();
					$obj['cid'] = trim($merchant->program_id);
					$obj['name'] = substr(trim(html_entity_decode($merchant->program_name)), 0, 150);
					$merchants[] = $obj;
				}
			}
		}
		
		

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		/*
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);
		for ($i = 0; $i < $dateArraySize; $i++) {
			$params = $this->_params;
			$params["start_date"] = $dateArray[$i]->toString("yyyy-MM-dd");
			$params["end_date"] = $dateArray[$i]->toString("yyyy-MM-dd");
			$result = $this->_apiClient->call('optionalInfo', $params, 'http://soapinterop.org/', 'http://soapinterop.org/');
			$xml = simplexml_load_string(html_entity_decode($result), null);
			foreach ($xml->optstats as $stats){
				$merchantId = (int) trim($stats->ProgramID);
				if (in_array($merchantId, $merchantList)) {
					$transaction = Array();
					$transaction['merchantId'] = $merchantId;
					$transactionDate = $dateArray[$i];
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
	
					$transaction['unique_id'] = trim($stats->TransactionID);
					
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					
					$amount = explode(" ", trim($stats->Amount));
					$transaction['amount'] = (double) $amount[1];
					$commission = explode(" ", trim($stats->Commission));
					$transaction['commission'] = (double) $commission[1];
					$totalTransactions[] = $transaction;
				}
			}
		}
		*/
		
		
		return $totalTransactions;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverviews = Array();
		
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);
		for ($i = 0; $i < $dateArraySize; $i++) {
			$params = $this->_params;
			$params["start_date"] = $dateArray[$i]->toString("yyyy-MM-dd");
			$params["end_date"] = $dateArray[$i]->toString("yyyy-MM-dd");
			$result = $this->_apiClient->call('monthlyStatsInfo', $params, 'http://soapinterop.org/', 'http://soapinterop.org/');
			if (!is_array($result)){
				$xml = simplexml_load_string(html_entity_decode($result), null);
				foreach ($xml->program_stats as $stats){
					$merchantId = (int) trim($stats->program_id);
					if (in_array($merchantId, $merchantList)) {
						$overview = Array();
						$payout = $stats->payout;
						$overview['merchantId'] = $merchantId;
						$overviewDate = $dateArray[$i];
						$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
						$overview['click_number'] = trim($stats->clicks);
						$overview['impression_number'] = trim($stats->impressions);
						$overview['transaction_number'] = trim($stats->number_of_sales);
						$overview['transaction_confirmed_value'] = 0;
						$overview['transaction_confirmed_commission'] = 0;
						$overview['transaction_pending_value'] = 0;
						$overview['transaction_pending_commission'] = 0;
						$overview['transaction_declined_value'] = 0;
						$overview['transaction_declined_commission'] = 0;
						$overview['transaction_paid_value'] = 0;
						$overview['transaction_paid_commission'] = 0;
						
						$total_sales = substr(trim($stats->total_sales), 1);
						$total = substr(trim($stats->total), 1);
						if ($total_sales != null){
							$overview['transaction_confirmed_value'] = $total_sales;
						}
						
						$overview['transaction_confirmed_commission'] = $total;
	
						
						$totalOverviews[] = $overview;
					}
				}
			}
		}

		return $totalOverviews;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		return $paymentHistory;
	}
}
