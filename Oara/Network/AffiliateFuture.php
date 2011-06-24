<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Af
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_AffiliateFuture extends Oara_Network{
    /**
     * Export Transaction Parameters
     * @var array
     */
	private $_exportTransactionParameters = null;
    /**
     * Export Overview Parameters
     * @var array
     */
	private $_exportOverviewParameters = null;

    /**
     * Client 
     * @var unknown_type
     */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $af
	 * @return Oara_Network_Af_Export
	 */
	public function __construct($credentials)
	{
		$user = $credentials['user'];
        $password = $credentials['password'];

		$loginUrl = 'http://affiliates.affiliatefuture.com/login.aspx?';
        
		$valuesLogin = array(new Oara_Curl_Parameter('username', $user),
                             new Oara_Curl_Parameter('password', $password),
                             new Oara_Curl_Parameter('Submit', 'Login Now')
                             );

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/login.aspx?', $valuesLogin);
		$this->_client->get($urls);
		
        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('username', $user),
                           							new Oara_Curl_Parameter('password', $password));  
                                                   
    	$this->_exportOverviewParameters = array(); 
                                               
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/myaccount/invoices.aspx', array());
       
        $result = $this->_client->get($urls);
        if (!preg_match("/Logout/", $result[0], $matches)){
            $connection = false;
        }
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array()){
		$merchants = Array();
        
        $merchantExportList = self::readMerchants();
        foreach ($merchantExportList as $merchant){
            $obj = Array();
	        $obj['cid'] = $merchant['cid'];
	        $obj['name'] = $merchant['name'];
	        $merchants[] = $obj;
        }
        return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		
		$nowDate = new Zend_Date();
		
		$dStartDate = clone $dStartDate;
		$dStartDate->setLocale('en');
	    $dStartDate->setHour("00");
        $dStartDate->setMinute("00");
        $dStartDate->setSecond("00");
        $dEndDate = clone $dEndDate;
        $dEndDate->setLocale('en');
        $dEndDate->setHour("23");
        $dEndDate->setMinute("59");
        $dEndDate->setSecond("59");
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString("dd-MMM-yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString("dd-MMM-yyyy"));
		$transactions = Array();
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://ws-external.afnt.co.uk/apiv1/AFFILIATES/affiliatefuture.asmx/GetTransactionListbyDate?', $valuesFromExport);
        $urls[] = new Oara_Curl_Request('http://ws-external.afnt.co.uk/apiv1/AFFILIATES/affiliatefuture.asmx/GetCancelledTransactionListbyDate?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		for ($i = 0 ; $i < count($urls); $i++){
			$xml = self::loadXml($exportReport[$i]);
			if (isset($xml->error)){
				throw new Exception('Error connecting with the server');
			}
			if(isset($xml->TransactionList)){
				foreach ($xml->TransactionList as $transaction) {
					$date = new Zend_Date(self::findAttribute($transaction, 'TransactionDate'),"yyyy-MM-ddTHH:mm:ss");
					
					if (in_array((int)self::findAttribute($transaction, 'ProgrammeID'),$merchantList)
					    && $date->compare($dStartDate) >= 0 
					    && $date->compare($dEndDate) <= 0) {
					    	
					   	$obj = Array();
					    	
					    $obj['merchantId'] = self::findAttribute($transaction, 'ProgrammeID');
						$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
						$obj['program'] = self::findAttribute($transaction, 'ProgrammeName');
						$obj['website'] = self::findAttribute($transaction, 'TrackingReference');
	
						if ($i == 0){
							if (Oara_Utilities::numberOfDaysBetweenTwoDates($date, $nowDate) > 5){
								$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
							} else {
								$obj['status'] = Oara_Utilities::STATUS_PENDING;
							}
						} else if ($i == 1){
							$obj['status'] = Oara_Utilities::STATUS_DECLINED;
						}
						
						$obj['amount'] = self::findAttribute($transaction, 'SaleValue');
						$obj['commission'] = self::findAttribute($transaction, 'SaleCommission');
						
	
						$transactions[] = $obj;
					}
				}
			}
		}
		

		return $transactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$overviewArray = Array();

		if (count($transactionList) > 0){
			$transactionList = Oara_Utilities::transactionMapPerDay($transactionList);
			$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
			$dateArraySize = sizeof($dateArray);
			for ($i = 0; $i < $dateArraySize; $i++){
				for( $j = 0; $j < count($merchantList); $j++){
				
					$transactionDateArray = Oara_Utilities::getDayFromArray($merchantList[$j], $transactionList, $dateArray[$i]);
					$groupMap = array();
					if (count($transactionDateArray) > 0 ){
		                $groupMap = self::groupOverview($groupMap, $transactionDateArray);
		            }
		            foreach($groupMap as $merchant => $groupMerchant){
			            foreach($groupMerchant as $link => $groupLink){
				            foreach($groupLink as $website => $groupWebsite){
				            	$groupWebsite['merchantId'] = $merchant;
				            	$groupWebsite['link'] = $link;
				            	
				            	$groupWebsite['website'] = $website;
				            	$groupWebsite['date'] = $dateArray[$i]->toString("yyyy-MM-dd HH:mm:ss");
				            	if (Oara_Utilities::checkRegister($groupWebsite)){
				            		$overviewArray[] = $groupWebsite;
				            	}
			                }
			            }
		            }
				}
			}
		}
		return $overviewArray;
	}
	/**
     * Group the overview
     * @param $groupMap - map where we are grouping
     * @param $registers - registers to add
     * @return none
     */
    public function groupOverview(array $groupMap = null, array $registers = null)
    {
        foreach ($registers as $register){
        	if (!isset($register['link']) || $register['link'] === null){
        		$register['link'] = '';
        	}
            if (!isset($register['website']) || $register['website'] === null){
                $register['website'] = '';
            }
            
        	if (!isset($groupMap[$register['merchantId']])){
        		$groupMap[$register['merchantId']] = array();
        	}
        	
        	if (!isset($groupMap[$register['merchantId']][$register['link']])){
        		$groupMap[$register['merchantId']][$register['link']] = array();
        	}
        	if(!isset($groupMap[$register['merchantId']][$register['link']][$register['website']])){
        		$overView = array();
        		$overView['click_number'] = 0;
        		$overView['impression_number'] = 0;
        		$overView['transaction_number'] = 0;
        		$overView['transaction_confirmed_value'] = 0;
        		$overView['transaction_pending_value'] = 0;
        		$overView['transaction_declined_value'] = 0;
        		$overView['transaction_confirmed_commission'] = 0;
                $overView['transaction_pending_commission'] = 0;
                $overView['transaction_declined_commission'] = 0;
        		
        		$groupMap[$register['merchantId']][$register['link']][$register['website']] = $overView;
        	}
        	if (isset($register['clicks'])){
        		$groupMap[$register['merchantId']][$register['link']][$register['website']]['click_number'] += (int)$register['clicks'];
        	}
            if (isset($register['impressions'])){
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['impression_number'] += (int)$register['impressions'];
            }
            if (isset($register['status'])){
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['transaction_number'] += 1;
            }
            if (isset($register['status']) && $register['status'] == Oara_Utilities::STATUS_CONFIRMED){
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['transaction_confirmed_value'] += (double)$register['amount'];
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['transaction_confirmed_commission'] += (double)$register['commission'];
            }
            if (isset($register['status']) && $register['status'] == Oara_Utilities::STATUS_PENDING){
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['transaction_pending_value'] += (double)$register['amount'];
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['transaction_pending_commission'] += (double)$register['commission'];
            }
            if (isset($register['status']) && $register['status'] == Oara_Utilities::STATUS_DECLINED){
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['transaction_declined_value'] += (double)$register['amount'];
                $groupMap[$register['merchantId']][$register['link']][$register['website']]['transaction_declined_commission'] += (double)$register['commission'];
            }
        }
        return $groupMap;
    }
	/**
     * Read the merchants in the table
     * @return array
     */
    public function readMerchants(){
    	$merchantList = array();
    	$urls = array();
        $urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/myprogrammes/default.aspx', array());
        $exportReport = $this->_client->get($urls);
        
        /*** load the html into the object ***/
	    $doc = new DOMDocument();
	    libxml_use_internal_errors(true);
	    $doc->validateOnParse = true;
	    $doc->loadHTML($exportReport[0]);
	    $tableList = $doc->getElementsByTagName('table');
	    
	    $merchantTable = $tableList->item(16)->childNodes;
    	for ($i = 1; $i < $merchantTable->length -1; $i++){
    		$merchant = array();
    		
    		$registerLine = $merchantTable->item($i);
    		$register = $registerLine->childNodes;
    		$attributeName = trim($register->item(0)->nodeValue);
    		$attributeUrl = $register->item(1)->childNodes->item(0)->childNodes->item(1)->getAttribute('href');
			$merchant['name'] = trim($attributeName);
			
	    	$parseUrl = parse_url($attributeUrl);
	        $parameters = explode('&', $parseUrl['query']);
	        $oaraCurlParameters = array();
	        foreach($parameters as $parameter){
	        	$parameterValue = explode('=', $parameter);
	            if ($parameterValue[0] == 'id'){
	            	$merchant['cid'] = $parameterValue[1];
	            }
	        }
	        $merchantList[] = $merchant;
    	}
        return $merchantList;
    }


	/**
	 * Cast the XMLSIMPLE object into string
	 * @param $object
	 * @param $attribute
	 * @return unknown_type
	 */
	private function findAttribute( $object = null, $attribute = null) {
		$return = null;
		$return = trim($object->$attribute);
		return $return;
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
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	
    	$urls = array();
        $urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/myaccount/invoices.aspx', array());
        $exportReport = $this->_client->get($urls);
    	
		/*** load the html into the object ***/
	    $doc = new DOMDocument();
	    libxml_use_internal_errors(true);
	    $doc->validateOnParse = true;
	    $doc->loadHTML($exportReport[0]);
	    $tableList = $doc->getElementsByTagName('table');
	    $registerTable = $tableList->item(12);
    	if ($registerTable == null){
			throw new Exception ('Fail getting the payment History');
		}
		
		$registerLines = $registerTable->childNodes;
		for ($i = 1;$i < $registerLines->length ;$i++) {
			$registerLine = $registerLines->item($i)->childNodes;
			$obj = array();
			$date = new Zend_Date(trim($registerLine->item(1)->nodeValue), "dd/MM/yyyy");
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
			$obj['pid'] = trim($registerLine->item(0)->nodeValue);
			$value = substr(trim($registerLine->item(4)->nodeValue),4);
			$obj['value'] = Oara_Utilities::parseDouble($value);
			$obj['method'] = 'BACS';
			$paymentHistory[] = $obj;
		}
    	
    	return $paymentHistory;
    }
	
}