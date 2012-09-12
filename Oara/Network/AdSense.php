<?php
require_once "GoogleApiClient/src/apiClient.php";
require_once "GoogleApiClient/src/contrib/apiAdsenseService.php";
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_As
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_AdSense extends Oara_Network{

	/**
	 * Adsense Client
	 * @var unknown_type
	 */
	private $_adsense = null;
	/**
	 * Constructor and Login
	 * @param $buy
	 * @return Oara_Network_Buy_Api
	 */
	public function __construct($credentials)
	{
		$client = new apiClient();
		$client->setApplicationName("AffJet");
		$client->setClientId('16630800841-enfgglm0okfiafv2ci042r27f0gfik44.apps.googleusercontent.com');
		$client->setClientSecret('tsZR2ZFiexQl6JN9xs3QuBVL');
		$client->setAccessToken($credentials['oauth2']);
		$this->_client = $client;
		$this->_adsense = new apiAdsenseService($client);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		if ($this->_client->getAccessToken()){
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList(){
		$merchants = Array();
			
		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Google AdSense";
		$obj['url'] = "www.google.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
		$totalTransactions = array();
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
		$overviewArray = array();
		
		
		$report = $this->_adsense->reports->generate($dStartDate->toString("YYYY-MM-dd"), $dEndDate->toString("YYYY-MM-dd"), array("dimension"=>"DATE", "metric"=>array("PAGE_VIEWS", "CLICKS", "EARNINGS"), "sort"=>"DATE"));
		
		$firstDayMonth = new Zend_Date();
		$firstDayMonth->setDay(1);
		$firstDayMonth->setHour("00");
		$firstDayMonth->setMinute("00");
		$firstDayMonth->setSecond("00");
		foreach ($report["rows"] as $row){
				$obj = array();
				$obj['merchantId'] = 1;
				$overviewDate = new Zend_Date($row[0],"yyyy-MM-dd");
				$overviewDate->setHour("00");
				$overviewDate->setMinute("00");
				$overviewDate->setSecond("00");
				$obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				
				$obj['transaction_number'] = 0;
				$obj['transaction_confirmed_commission'] =  0;
				$obj['transaction_confirmed_value'] =  0;
				$obj['transaction_pending_commission'] =  0;
				$obj['transaction_pending_value'] =  0;
				$obj['transaction_declined_commission'] = 0;
				$obj['transaction_declined_value'] = 0;
				$obj['transaction_paid_commission'] = 0;
				$obj['transaction_paid_value'] = 0;
					
				$obj['impression_number'] = (int)Oara_Utilities::parseDouble($row[1]);
				$obj['click_number'] =  Oara_Utilities::parseDouble($row[2]);
				if ($firstDayMonth->compare($overviewDate) <= 0){
					$obj['transaction_pending_commission'] = Oara_Utilities::parseDouble($row[3]);
					$obj['transaction_pending_value'] = Oara_Utilities::parseDouble($row[3]);
				} else {
					$obj['transaction_confirmed_commission'] =  Oara_Utilities::parseDouble($row[3]);
					$obj['transaction_confirmed_value'] =  Oara_Utilities::parseDouble($row[3]);
				}
				
				if (Oara_Utilities::checkRegister($obj)){
					$overviewArray[] = $obj;
				}
		}
		
		return $overviewArray;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
		$paymentHistory = array();
		
		return $paymentHistory;
	}
}
