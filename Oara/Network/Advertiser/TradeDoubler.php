<?php
/**
 * API Class
 *
 * @author Carlos Morillo Merino
 * @category Oara_Network_Advertiser_Tradedoubler
 * @copyright Fubra Limited
 * @version Release: 01.00
 *         
 */
class Oara_Network_Advertiser_TradeDoubler extends Oara_Network {
	
	/**
	 * Client
	 *
	 * @var unknown_type
	 */
	private $_client = null;
	
	private $_currency = null;
	
	/**
	 * Constructor and Login
	 *
	 * @param $buy
	 * @return Oara_Network_Publisher_Buy_Api
	 */
	public function __construct($credentials) {
		$user = $credentials ['user'];
		$password = $credentials ['password'];
		
		$this->_currency = $credentials["currency"];
		
		$loginUrl = 'https://login.tradedoubler.com/pan/login';
		
		$valuesLogin = array (
				new Oara_Curl_Parameter ( 'j_username', $user ),
				new Oara_Curl_Parameter ( 'j_password', $password )
		);
		
		$this->_client = new Oara_Curl_Access ( $loginUrl, $valuesLogin, $credentials );

		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {		
		
		$connection = false;
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://login.tradedoubler.com/pan/mStartPage.action?resetMenu=true', array () );
		$exportReport = $this->_client->get ( $urls );
		if (preg_match ( "/logout/", $exportReport [0], $matches )) {
			$connection = true;
		}
		return $connection;
		
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array ();
		
		$valuesFromExport = array ();
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://login.tradedoubler.com/pan/mStartPage.action?resetMenu=true', $valuesFromExport );
		$exportReport = $this->_client->get ( $urls );
		
		$dom = new Zend_Dom_Query ( $exportReport [0] );
		$results = $dom->query ( '#programChooserId' );
		$merchantLines = $results->current ()->childNodes;
		for($i = 0; $i < $merchantLines->length; $i ++) {
			$cid = $merchantLines->item ( $i )->attributes->getNamedItem ( "value" )->nodeValue;
			if (is_numeric ( $cid )) {
				$obj = array ();
				$name = $merchantLines->item ( $i )->nodeValue;
				$obj = array ();
				$obj ['cid'] = $cid;
				$obj ['name'] = $name;
				$merchants [] = $obj;
			}
		}
		
		return $merchants;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array ();
		
		$valuesFromExport = array ();
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'reportName', 'mMerchantSaleAndLeadBreakdownReport' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'programCountry' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'programName' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'time_of_visit' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'timeOfEvent' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'in_session' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'eventName' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'siteName' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'product_name' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'productNrOf' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'product_value' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'open_product_feeds_id' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'open_product_feeds_name' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'voucher_code' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'deviceType' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'os' );		
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'browser' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'vendor' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'device' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'affiliateCommission' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'totalCommission' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'segmentName' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'graphicalElementId' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'pf_product' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'orderValue' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'order_number' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'pending_status' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'columns', 'epi1' );
/**/	//$valuesFromExport [] = new Oara_Curl_Parameter ( 'startDate', '21/08/2013' );
/**/	//$valuesFromExport [] = new Oara_Curl_Parameter ( 'endDate', '21/08/2014' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'startDate', $dStartDate->toString ( "dd/MM/yyyy" ) );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'endDate', $dEndDate->toString ( "dd/MM/yyyy" ) );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'isPostBack', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.lastOperator', '/' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'interval', 'MONTHS' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'segmentId', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'favoriteDescription', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'currencyId', $this->_currency );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'run_as_organization_id', '' );
/**/	$valuesFromExport [] = new Oara_Curl_Parameter ( 'eventId', '5' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'minRelativeIntervalStartTime', '0' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.summaryType', 'NONE' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'includeMobile', '1' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.operator1', '/' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'latestDayToExecute', '0' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'showAdvanced', 'true' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.midFactor', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_MMERCHANTSALESANDLEADBREAKDOWNREPORT_TITLE' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'setColumns', 'true' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.columnName1', 'organizationId' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.columnName2', 'organizationId' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'reportPrograms', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.midOperator', '/' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'viewType', '1' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'favoriteName', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'affiliateId', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'dateType', '1' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'period', 'custom_period' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'geId', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'tabMenuName', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'maxIntervalSize', '12' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'allPrograms', 'false' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'favoriteId', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.name', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'filterOnTimeHrsInterval', 'false' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'customKeyMetricCount', '0' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'metric1.factor', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'showFavorite', 'false' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'separator', '' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'format', 'CSV' );
		$valuesFromExport [] = new Oara_Curl_Parameter ( 'programId', '' );
		
		for ($i = 0; $i < count($merchantList); $i++){

			$programId = array_pop($valuesFromExport);
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'programId', $merchantList[$i] );
			$urls = array ();
			$urls [] = new Oara_Curl_Request ( 'https://login.tradedoubler.com/pan/mReport3.action?', $valuesFromExport );				
			try{
				$result = $this->_client->get($urls);
			} catch (Exception $e){
				return $transactions;
			}				
			$exportData = str_getcsv($result[0], "\n");
				
			for ($j = 2; $j < count($exportData)-1; $j++){				
				$transactionExportArray = str_getcsv($exportData[$j], ";");
								
				if(is_numeric($transactionExportArray[17])){
					$transaction = Array();
					$transaction['unique_id'] = $transactionExportArray[6]; //order nr
					if ($transactionExportArray[26]!=null){
						$transaction['custom_id'] = $transactionExportArray[26]; //epi1
					}
					$transaction['merchantId'] = $merchantList[$i];
					$transactionDate = new Zend_Date($transactionExportArray[4], 'dd/MM/yy HH:mm:ss CEST');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					$transaction['amount'] = $transactionExportArray[17];
					$transaction['commission'] = $transactionExportArray[24];
					
					if ($transactionExportArray[25] == 'Approved') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
					if ($transactionExportArray[25] == 'Pending') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
					if ($transactionExportArray[25] == 'Deleted') {
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					}
					
					$totalTransactions[] = $transaction;
				}			
			}
		}
		
		return $totalTransactions;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array ();
		
		return $paymentHistory;
	}
	
	/**
	 *
	 *
	 *
	 * It returns the transactions for a payment
	 *
	 * @param int $paymentId        	
	 */
	public function paymentTransactions($paymentId, $merchantList, $startDate) {
		$transactionList = array ();
		
		return $transactionList;
	}
	
	/**
	 *
	 *
	 * Function that Convert from a table to Csv
	 * 
	 * @param unknown_type $html        	
	 */
	private function htmlToCsv($html) {
		$html = str_replace ( array (
				"\t",
				"\r",
				"\n" 
		), "", $html );
		$csv = "";
		$dom = new Zend_Dom_Query ( $html );
		$results = $dom->query ( 'tr' );
		$count = count ( $results ); // get number of matches: 4
		foreach ( $results as $result ) {
			
			$domTd = new Zend_Dom_Query ( self::DOMinnerHTML($result) );
			$resultsTd = $domTd->query ( 'td' );
			$countTd = count ( $resultsTd );
			$i = 0;
			foreach( $resultsTd as $resultTd) {
				$value = $resultTd->nodeValue;
				if ($i != $countTd - 1) {
					$csv .= trim ( $value ) . ";,";
				} else {
					$csv .= trim ( $value );
				}
				$i++;
			}
			$csv .= "\n";
		}
		$exportData = str_getcsv ( $csv, "\n" );
		return $exportData;
	}
	/**
	 *
	 *
	 * Function that returns the innet HTML code
	 * 
	 * @param unknown_type $element        	
	 */
	private function DOMinnerHTML($element) {
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ( $children as $child ) {
			$tmp_dom = new DOMDocument ();
			$tmp_dom->appendChild ( $tmp_dom->importNode ( $child, true ) );
			$innerHTML .= trim ( $tmp_dom->saveHTML () );
		}
		return $innerHTML;
	}
}
