<?php
/**
 * Api Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Wow
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_WowTrk extends Oara_Network{
    /**
     * Soap client.
     */
	private $_apiClient = null;
	 /**
     * Export client.
     */
	private $_exportClient = null;
	
	/**
	 * Credentials Export Parameters
	 * @var array
	 */
	private $_credentialsParameters = array ();

    /**
     * merchantMap.
     * @var array
     */
    private $_merchantMap = array();
    
    /**
     * Api key.
     */
	private $_apiPassword = null;
    
    /**
     * page Size.
     */
	private $_pageSize = 200;
    
    /**
     * Constructor.
     * @param $wow
     * @return Oara_Network_Aw_Api
     */
	public function __construct($credentials)
	{
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_apiPassword = $credentials['apiPassword'];
        
		//login through wow website
	    $loginUrl = 'http://p.wowtrk.com/';
	    $valuesLogin = array(new Oara_Curl_Parameter('data[User][email]', $user),
							new Oara_Curl_Parameter('data[User][password]', $password),
							new Oara_Curl_Parameter('_method', 'POST')
                           );
        
		$this->_exportClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		try{
			//http://p.wowtrk.com/offers/offers.csv
			
			
			$connection = true;
		} catch (Exception $e){
			
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array())
	{
		$merchants= array();
		
		$valuesFromExport = array();
        $valuesFromExport[] = new Oara_Curl_Parameter('api_key', $this->_apiPassword);
        $valuesFromExport[] = new Oara_Curl_Parameter('limit', $this->_pageSize);
        $valuesFromExport[] = new Oara_Curl_Parameter('page', 1);
        
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://p.wowtrk.com/offers/offers.xml?', $valuesFromExport);   
		$exportReport = $this->_exportClient->get($urls);
		
		
		$exportData = self::loadXml($exportReport[0]);
		
        foreach ($exportData->offer as $merchant) {
            $obj = array();
            $obj['cid'] = (int)$merchant->id;
            $obj['name'] = (string)$merchant->name;
            $obj['url'] = (string)$merchant->preview_url;
            $merchants[] = $obj;
        }
		return $merchants;
	}
	/**
	 * Convert the string in xml object.
	 * @param $exportReport
	 * @return xml
	 */
	private function loadXml($exportReport = null){
		$xml = simplexml_load_string($exportReport, null, LIBXML_NOERROR | LIBXML_NOWARNING);
		/**
		if($xml == false){
			throw new Exception('Problems in the XML');
		}
		*/
		return $xml;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
     */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
	{	
		$totalTransactions = array();
		
		$dStartDate = clone $dStartDate;
	    $dStartDate->setHour("00");
        $dStartDate->setMinute("00");
        $dStartDate->setSecond("00");
        $dEndDate = clone $dEndDate;
        $dEndDate->setHour("23");
        $dEndDate->setMinute("59");
        $dEndDate->setSecond("59");
			
		$params = $this->_credentialsParameters;

		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
        for ($i = 0; $i < sizeof($dateArray); $i++){
        	$groupMap = array();
        	$auxStartDate = clone $dateArray[$i];
        	$auxStartDate->setHour("00");
            $auxStartDate->setMinute("00");
            $auxStartDate->setSecond("00");
            
            $transactionList = $this->_apiClient->getAffiliateAggregateStatistics($params['client'],$params['password'], 
																				  $params['add_code'], $auxStartDate->toString("yyyy-MM-dd"), 
																			      $auxStartDate->toString("yyyy-MM-dd"));
									      
			if (!preg_match("/No Results found/", $transactionList, $matches)){
				$transactionList = str_replace(array("&pound;"), array("£"), $transactionList);
				$xmlObj = simplexml_load_string($transactionList);
				foreach ($xmlObj->children() as $transaction) {
					$merchantId = (string)$transaction->campaign_id;
					if (in_array($merchantId, $merchantList)){
						
						$obj = array();
						$obj['merchantId'] = $merchantId;
		                $obj['date'] = $auxStartDate->toString("yyyy-MM-dd HH:mm:ss");
	                	$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
		                        
	                    $obj['amount'] = Oara_Utilities::parseDouble((string)$transaction->sales);  
	                	$obj['commission'] = Oara_Utilities::parseDouble((string)$transaction->payout);
	                	if ($obj['amount'] != 0 || $obj['commission'] != 0){
	                		$totalTransactions[] = $obj;
	                	}
					}
				}
				
			}	
            
        }
		return $totalTransactions;
	}
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
     */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
	{
		$totalOverview = array();
		//At first, we need to be sure that there are some data.
	    $auxStartDate = clone $dStartDate;
	    $auxStartDate->setHour("00");
        $auxStartDate->setMinute("00");
        $auxStartDate->setSecond("00");
        $auxEndDate = clone $dEndDate;
        $auxEndDate->setHour("23");
        $auxEndDate->setMinute("59");
        $auxEndDate->setSecond("59");
            
		$dStartDate = clone $dStartDate;
	    $dStartDate->setHour("00");
        $dStartDate->setMinute("00");
        $dStartDate->setSecond("00");
        $dEndDate = clone $dEndDate;
        $dEndDate->setHour("23");
        $dEndDate->setMinute("59");
        $dEndDate->setSecond("59");
			
		$params = $this->_credentialsParameters;

		
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
        for ($i = 0; $i < sizeof($dateArray); $i++){
        	$groupMap = array();
        	$auxStartDate = clone $dateArray[$i];
        	$auxStartDate->setHour("00");
            $auxStartDate->setMinute("00");
            $auxStartDate->setSecond("00");
            
            $overviewList = $this->_apiClient->getAffiliateAggregateStatistics($params['client'],$params['password'], 
																				  $params['add_code'], $auxStartDate->toString("yyyy-MM-dd"), 
																			      $auxStartDate->toString("yyyy-MM-dd"));
									      
			if (!preg_match("/No Results found/", $overviewList, $matches)){
				$overviewList = str_replace(array("&pound;"), array("£"), $overviewList);
				$xmlObj = simplexml_load_string($overviewList);
				foreach ($xmlObj->children() as $overview) {
					$merchantId = (string)$overview->campaign_id;
					if (in_array($merchantId, $merchantList)){
						$obj = array();
						$obj['merchantId'] = $merchantId;
		                $obj['date'] = $auxStartDate->toString("yyyy-MM-dd HH:mm:ss");
	                	$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
	                	
	                	$obj['click_number'] = 0;
	                	if ((string)$overview->clicks != null){
	                		$obj['click_number'] = (string)$overview->clicks;
	                	}
						$obj['impression_number'] = 0;
	                	if ((string)$overview->impressions != null){
	                		$obj['impression_number'] = (string)$overview->impressions;
	                	}
	                	
	                	$obj['transaction_number'] = 0;
	                	$obj['transaction_confirmed_value'] = 0;
	                	$obj['transaction_confirmed_commission'] = 0;
	                	$obj['transaction_pending_value'] = 0;
	                	$obj['transaction_pending_commission'] = 0;
	                	$obj['transaction_declined_value'] = 0;
	                	$obj['transaction_declined_commission'] = 0;
	                	
	                    $obj['transaction_confirmed_value'] = Oara_Utilities::parseDouble((string)$overview->sales);  
	                	$obj['transaction_confirmed_commission'] = Oara_Utilities::parseDouble((string)$overview->payout);
	                	if ($obj['transaction_confirmed_value'] != 0 || $obj['transaction_confirmed_commission'] != 0){
	                		$obj['transaction_number']++;
	                	}
	                	if (Oara_Utilities::checkRegister($obj)){
	                		$totalOverview[] = $obj;
	                	}
					}
				}
				
			}	
            
        }
	    return $totalOverview;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	/*
    	$urls = array();
		$urls[] = new Oara_Curl_Request('https://a.wowtrk.com/partners/payment_ecc.html?', array());
		$content = $this->_exportClient->get($urls);
		
	    $doc = new DOMDocument();
	    libxml_use_internal_errors(true);
	    $doc->validateOnParse = true;
	    $doc->loadHTML($content[0]);
	    $tableList = $doc->getElementsByTagName('table');
    	$registerTable = $tableList->item(6);
    	if ($registerTable != null){
			$registerLines = $registerTable->childNodes;
			for ($i = 1;$i < $registerLines->length;$i++) {
				$registerLine = $registerLines->item($i);
				$register = $registerLine->childNodes;
				
				$obj = array();
				preg_match( '/[0-9]+(,[0-9]{3})*(\.[0-9]{2})?$/', $register->item(2)->nodeValue, $matches);
				$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
				$paymentDate = new Zend_Date($register->item(0)->nodeValue, "yyyy-MM-dd");
				$obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = $paymentDate->toString("yyyyMMdd");
				$obj['method'] = 'BACS';
				
				$paymentHistory[] = $obj;
			}
    	}
    	*/
    	return $paymentHistory;
    }
	
}