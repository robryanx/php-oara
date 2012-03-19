<?php
/**
 * Api Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Aw
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_AffiliateWindow extends Oara_Network{
    /**
     * Soap client.
     */
	private $_apiClient = null;
	
	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array ('iId'=>'cid',
                                                      'sName'=>'name',
                                                      'sDisplayUrl'=>'url'
	                                                  );
	/**
     * Converter configuration for the transactions.
     * @var array
     */
	private $_transactionConverterConfiguration = Array ('sStatus'=>'status',
	                                                     'fSaleAmount'=>'amount',
	                                                     'fCommissionAmount'=>'commission',
	                                                     'dTransactionDate'=>'date',
	                                                     'sClickref'=>'custom_id',
													     'iMerchantId'=>'merchantId'
														);
														
	/**
     * Converter configuration for the clicks.
     * @var array
     */
    private $_clickConverterConfiguration = Array ('iClicks'=>'clicks',
                                                   'date'=>'date',
    											   'sMerchantName'=>'merchantName'
                                                   );
    /**
     * Converter configuration for the impressions.
     * @var array
     */
    private $_impressionConverterConfiguration = Array ('iImpressions'=>'impressions',
	                                                    'date'=>'date',
    													'sMerchantName'=>'merchantName'
                                                        );
    								
	/**
	 * Overview Export Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;
   
				                                 
    /**
     * merchantMap.
     * @var array
     */
    private $_merchantMap = array();
    
    /**
     * page Size.
     */
	private $_pageSize = 100;
	
	/**
     * affiliate Id.
     */
	private $_affiliateId = 100;
    
    /**
     * Constructor.
     * @param $affiliateWindow
     * @return Oara_Network_Aw_Api
     */
	public function __construct($credentials)
	{
		ini_set('default_socket_timeout','120');
        $user = $credentials['user'];
        $password = $credentials['apiPassword'];
        $passwordExport = $credentials['password'];
        
        $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('post', 'yes'),
        										 new Oara_Curl_Parameter('merchant', ''),
        										 new Oara_Curl_Parameter('limit', '25'),
	                                             new Oara_Curl_Parameter('submit.x', '75'),
	                                             new Oara_Curl_Parameter('submit.y', '11'),
	                                             new Oara_Curl_Parameter('submit', 'submit'));
       
		//Login to the website
		$validator = new Zend_Validate_EmailAddress();
		if ($validator->isValid($user)) {
			//login through darwin
			$loginUrl = 'https://darwin.affiliatewindow.com/login?';
			
		    $valuesLogin = array(new Oara_Curl_Parameter('email', $user),
								 new Oara_Curl_Parameter('password', $passwordExport),
								 new Oara_Curl_Parameter('formuserloginlogin', '')
                              	);
            $this->_exportClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

			$urls = array();
	        $urls[] = new Oara_Curl_Request('http://darwin.affiliatewindow.com/user/', array());
	        $exportReport = $this->_exportClient->get($urls);
			if (preg_match_all("/id=\"goDarwin(.*)\"/", $exportReport[0], $matches)){
				
				foreach ($matches[1] as $user){
					$urls = array();
			        $urls[] = new Oara_Curl_Request('http://darwin.affiliatewindow.com/affiliate/'.$user, array());
			        $exportReport = $this->_exportClient->get($urls);
					if (preg_match("/<li>Payment<ul><li><a class=\"arrow sectionList\" href=\"(.*)\">/", $exportReport[0], $matches)){
						$urls = array();
			        	$urls[] = new Oara_Curl_Request('http://darwin.affiliatewindow.com'.$matches[1], array());
			        	$exportReport = $this->_exportClient->get($urls);
					} else {
						throw new Exception("It couldn't connect to darwin");
					}
					
					$urls = array();
			        $urls[] = new Oara_Curl_Request('https://www.affiliatewindow.com/affiliates/accountdetails.php', array());
			        $exportReport = $this->_exportClient->get($urls);
					$dom = new Zend_Dom_Query($exportReport[0]);
					$apiPassword = $dom->query('#aw_api_password_hash');
					$apiPassword = $apiPassword->current();
    				if ($apiPassword != null && $apiPassword->nodeValue == $password){
    					break;
    				}
				}
				
			}
			
			
	        
		} else {
			throw new Exception("It's not an email");
		}
		
		$nameSpace = 'http://api.affiliatewindow.com/';
        
        $wsdlUrl = 'http://api.affiliatewindow.com/v3/AffiliateService?wsdl';
        //Setting the client.
		$this->_apiClient = new Oara_Import_Soap_Client($wsdlUrl, array('login' => $user,
		                                                      'encoding' => 'UTF-8',
		                                                      'password' => $password,
		                                                      'compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                                      'soap_version' => SOAP_1_1));
        //Setting the headers
		$soapHeader1 = new SoapHeader($nameSpace, 'UserAuthentication',
		                                          array('iId' => $user,
                                                        'sPassword' => $password,
                                                        'sType' => 'affiliate'),
		                                          true, $nameSpace);

	    $soapHeader2 = new SoapHeader($nameSpace, 'getQuota', true, true, $nameSpace);
	    
		//Adding the headers
		$this->_apiClient->addSoapInputHeader($soapHeader1, true);
		$this->_apiClient->addSoapInputHeader($soapHeader2, true);
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		try{
			
			$params = Array();
			$params['sRelationship'] = 'joined';
			$this->_apiClient->getMerchantList($params);
			
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
		$params = Array();
		$params['sRelationship'] = 'joined';
		
		$merchants = $this->_apiClient->getMerchantList($params)->getMerchantListReturn;
		$arrayMerchantIds = Array();
		foreach ($merchants as $merchant){
			$arrayMerchantIds[] = $merchant->iId;
		}
		$merchants = self::getMerchant($arrayMerchantIds);
		
		$this->_merchantMap = $merchantMap;
		foreach ($merchants as $merchant){
			if (!isset($this->_merchantMap[$merchant['name']])){
				$this->_merchantMap[$merchant['name']] = $merchant['cid'];
			}
			
		}
		return $merchants;
	}
    /**
     * Get the merchant for an Id
     * @param integer $merchantId
     * @return array
     */
    public function getMerchant(array $merchantIds = null)
    {
    	$merchantList = array();
        
        if ($merchantIds != null){
        	$iteration = 0;
        	$arraySlice = array_slice($merchantIds, $this->_pageSize*$iteration, $this->_pageSize);
        	while (!empty($arraySlice)){
        		$params = array();
        		$params['aMerchantIds'] = $arraySlice;
        
		        $merchantApiList = $this->_apiClient->getMerchant($params)->getMerchantReturn; 
		        $merchantList = array_merge($merchantList, Oara_Utilities::soapConverter($merchantApiList, $this->_merchantConverterConfiguration));
        		$iteration ++;
        		$arraySlice = array_slice($merchantIds, $this->_pageSize*$iteration, $this->_pageSize);
        	}
           
        }
        return $merchantList;
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
			
        $params = array();
		$params['sDateType'] = 'transaction';
		if ($merchantList != null){
			$params['aMerchantIds'] = $merchantList;
		}
		if ($dStartDate != null){
                $params['dStartDate'] = $dStartDate->toString("yyyy-MM-ddTHH:mm:ss");
		}
		if ($dEndDate != null){
                $params['dEndDate'] = $dEndDate->toString("yyyy-MM-ddTHH:mm:ss");
		}
		
	    $params['iOffset'] = null;
		
		$params['iLimit'] = $this->_pageSize;
		$transactionList = $this->_apiClient->getTransactionList($params);
		if (sizeof($transactionList->getTransactionListReturn) > 0){
			$totalTransactions = array_merge($totalTransactions, Oara_Utilities::soapConverter($transactionList->getTransactionListReturn, $this->_transactionConverterConfiguration));
			$iteration = self::calculeIterationNumber($transactionList->getTransactionListCountReturn->iRowsAvailable, $this->_pageSize);
			unset($transactionList);
			for($j = 1; $j < $iteration; $j++){
				$params['iOffset'] = $this->_pageSize*$j;
				$transactionList = $this->_apiClient->getTransactionList($params);
				
				$totalTransactions = array_merge($totalTransactions, Oara_Utilities::soapConverter($transactionList->getTransactionListReturn, $this->_transactionConverterConfiguration));
				unset($transactionList);
				gc_collect_cycles();
			}
		    
		}
		return $totalTransactions;
	}
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
     */
	public function getOverviewList ($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
	{
		$totalOverview = Array();
		//At first, we need to be sure that there are some data.
	    $auxStartDate = clone $dStartDate;
	    $auxStartDate->setHour("00");
        $auxStartDate->setMinute("00");
        $auxStartDate->setSecond("00");
        $auxEndDate = clone $dEndDate;
        $auxEndDate->setHour("23");
        $auxEndDate->setMinute("59");
        $auxEndDate->setSecond("59");
            
        $params = Array();
        $params['sDateType'] = 'transaction';
        if( $dStartDate != null){
            $params['dStartDate'] = $auxStartDate->toString("yyyy-MM-ddTHH:mm:ss");
        }
        if ($dEndDate != null){
            $params['dEndDate'] = $auxEndDate->toString("yyyy-MM-ddTHH:mm:ss");
        }
        if ($merchantList != null){
            $params['aMerchantIds'] = $merchantList;
        }
            
        $params['iOffset'] = null;
            
        $params['iLimit'] = $this->_pageSize;

        $clickStats = $this->_apiClient->getClickStats($params);
        $impressionStats = $this->_apiClient->getImpressionStats($params);
        $transactionList = Oara_Utilities::transactionMapPerDay($transactionList);
        
        /* If we don't wan tot use click and impressions
        $dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
        for ($i = 0; $i < sizeof($dateArray); $i++){
        	$groupMap = array();
        	$auxStartDate = clone $dateArray[$i];
        	$auxStartDate->setHour("00");
            $auxStartDate->setMinute("00");
            $auxStartDate->setSecond("00");
        
			$transactionDateArray = array();
	        foreach ($transactionList as $merchantId => $data){
	        	$transactionDateArray = array_merge($transactionDateArray, Oara_Utilities::getDayFromArray($merchantId, $transactionList, $auxStartDate));
	        }
	                 
			if (count($transactionDateArray) > 0 ){
		    	$groupMap = self::groupOverview($groupMap, $transactionDateArray);
		  	}
		  	
		 	foreach($groupMap as $merchant => $overview){
		    	$overview['merchantId'] = $merchant;
		    	$overview['date'] = $auxStartDate->toString("yyyy-MM-dd HH:mm:ss");
		     	if (Oara_Utilities::checkRegister($overview)){
		      		$totalOverview[] = $overview;
		  		}
			}
			
        }
        */
        
        if (count($clickStats->getClickStatsReturn) > 0 
            || count($impressionStats->getImpressionStatsReturn) > 0
            || count($transactionList) > 0){
            	
            unset($clickStats);
            unset($impressionStats);
            
			$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
	        for ($i = 0; $i < sizeof($dateArray); $i++){
	        	$groupMap = array();
	        	$auxStartDate = clone $dateArray[$i];
	        	$auxStartDate->setHour("00");
	            $auxStartDate->setMinute("00");
	            $auxStartDate->setSecond("00");
	            
	            $auxEndDate = clone $dateArray[$i];
	            $auxEndDate->setHour("23");
	            $auxEndDate->setMinute("59");
	            $auxEndDate->setSecond("59");
	            
				$params = Array();
				$params['sDateType'] = 'transaction';
	            if( $dStartDate != null){
	                $params['dStartDate'] = $auxStartDate->toString("yyyy-MM-ddTHH:mm:ss");
	            }
	            if ($dEndDate != null){
	                $params['dEndDate'] = $auxEndDate->toString("yyyy-MM-ddTHH:mm:ss");
	            }
				if ($merchantList != null){
					$params['aMerchantIds'] = $merchantList;
				}
				
				$params['iOffset'] = null;
				
				$params['iLimit'] = $this->_pageSize;
	
				$clickStats = $this->_apiClient->getClickStats($params);
				if (count($clickStats->getClickStatsReturn) > 0){
				    $groupMap = self::groupOverview($groupMap, Oara_Utilities::soapConverter($clickStats->getClickStatsReturn, $this->_clickConverterConfiguration));
					$iteration = self::calculeIterationNumber($clickStats->getClickStatsCountReturn->iRowsAvailable, $this->_pageSize);
					unset($clickStats);
					for($j = 1; $j < $iteration; $j++){
						$params['iOffset'] = $this->_pageSize*$j;
						$clickStats = $this->_apiClient->getClickStats($params);
						$groupMap = self::groupOverview($groupMap, Oara_Utilities::soapConverter($clickStats->getClickStatsReturn, $this->_clickConverterConfiguration));
						unset($clickStats);
						gc_collect_cycles();
					}
				}
				$params['iOffset'] = null;
	            $impressionStats = $this->_apiClient->getImpressionStats($params);
                if (count($impressionStats->getImpressionStatsReturn) > 0){
                    $groupMap = self::groupOverview($groupMap, Oara_Utilities::soapConverter($impressionStats->getImpressionStatsReturn, $this->_impressionConverterConfiguration));
					$iteration = self::calculeIterationNumber($impressionStats->getImpressionStatsCountReturn->iRowsAvailable, $this->_pageSize);
                    unset($impressionStats);
					for($j = 1; $j < $iteration; $j++){
						$params['iOffset'] = $this->_pageSize*$j;
						$impressionStats = $this->_apiClient->getImpressionStats($params);
						$groupMap = self::groupOverview($groupMap, Oara_Utilities::soapConverter($impressionStats->getImpressionStatsReturn, $this->_impressionConverterConfiguration));
						unset($impressionStats);
						gc_collect_cycles();
                    }
                }
                $transactionDateArray = array();
                foreach ($transactionList as $merchantId => $data){
                	$transactionDateArray = array_merge($transactionDateArray, Oara_Utilities::getDayFromArray($merchantId, $transactionList, $auxStartDate));
                }
                 
	            if (count($transactionDateArray) > 0 ){
	                $groupMap = self::groupOverview($groupMap, $transactionDateArray);
	            }
	            foreach($groupMap as $merchant => $overview){
	            	$overview['merchantId'] = $merchant;
	            	$overview['date'] = $auxStartDate->toString("yyyy-MM-dd HH:mm:ss");
	            	if (Oara_Utilities::checkRegister($overview)){
	            		$totalOverview[] = $overview;
	            	}
	            }
	        }
        }
        
	    return $totalOverview;
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
        	if (!isset($register['merchantId']) || $register['merchantId'] === null){
                $register['merchantId'] = $this->_merchantMap[$register['merchantName']];
            }
            
        	if (!isset($groupMap[$register['merchantId']])){
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
        		
        		$groupMap[$register['merchantId']] = $overView;
        	}

        	if (isset($register['clicks'])){
        		$groupMap[$register['merchantId']]['click_number'] += (int)$register['clicks'];
        	}
            if (isset($register['impressions'])){
                $groupMap[$register['merchantId']]['impression_number'] += (int)$register['impressions'];
            }
            if (isset($register['status'])){
                $groupMap[$register['merchantId']]['transaction_number'] += 1;
            }
            if (isset($register['status']) && $register['status'] == Oara_Utilities::STATUS_CONFIRMED){
                $groupMap[$register['merchantId']]['transaction_confirmed_value'] += (double)$register['amount'];
                $groupMap[$register['merchantId']]['transaction_confirmed_commission'] += (double)$register['commission'];
            }
            if (isset($register['status']) && $register['status'] == Oara_Utilities::STATUS_PENDING){
                $groupMap[$register['merchantId']]['transaction_pending_value'] += (double)$register['amount'];
                $groupMap[$register['merchantId']]['transaction_pending_commission'] += (double)$register['commission'];
            }
            if (isset($register['status']) && $register['status'] == Oara_Utilities::STATUS_DECLINED){
                $groupMap[$register['merchantId']]['transaction_declined_value'] += (double)$register['amount'];
                $groupMap[$register['merchantId']]['transaction_declined_commission'] += (double)$register['commission'];
            }
        }
        return $groupMap;
    }
    /**
     * Calculate the number of iterations needed
     * @param $rowAvailable
     * @param $rowsReturned
     */
	private function calculeIterationNumber($rowAvailable, $rowsReturned){
		$iterationDouble = (double)($rowAvailable/$rowsReturned);
		$iterationInt = (int)($rowAvailable/$rowsReturned);
		if($iterationDouble > $iterationInt){
			$iterationInt++;
		}
		return $iterationInt;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.affiliatewindow.com/affiliates/payment.php?', array());
        $exportReport = $this->_exportClient->get($urls);
		/*** load the html into the object ***/
        $dom = new Zend_Dom_Query($exportReport[0]);
        $results = $dom->query('table .reportTable');
		foreach ($results as $result) {
		
			$registerLines = $result->childNodes;
			for ($i = 1;$i < $registerLines->length;$i++) {
				
				$registerLine = $registerLines->item($i);
				$linkList = $registerLine->getElementsByTagName('a');
	
				$obj = array();
				$date = new Zend_Date($linkList->item(0)->nodeValue, "dd/MM/yyyy");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$attrs = $linkList->item(0)->attributes;
	
	 			foreach ($attrs as $attrName => $attrNode){
	 				if ($attrName = 'href'){
		 				$parseUrl = parse_url(trim($attrNode->nodeValue));
				        $parameters = explode('&', $parseUrl['query']);
				        foreach($parameters as $parameter){
				        	$parameterValue = explode('=', $parameter);
				            if ($parameterValue[0] == 'paymentid'){
				            	$obj['pid'] = $parameterValue[1];
				            }
				        }
	 				}
	 			}
				$obj['value'] = Oara_Utilities::parseDouble(substr($linkList->item(3)->nodeValue,2));
				$obj['method'] = $linkList->item(2)->nodeValue;
				$paymentHistory[] = $obj;
			}
		}
    	return $paymentHistory;
    }
    /**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getCreatives()
	 */
	public function getCreatives(){
		$creativesMap = array();
    	$merchantList = self::getMerchantList();
    	
    	$urls = array();
		foreach ($merchantList as $merchant){
	       	$valuesFormExport = array();
	       	$valuesFormExport[] = new Oara_Curl_Parameter('getcode', 'getcode');
	       	$valuesFormExport[] = new Oara_Curl_Parameter('mid', $merchant['cid']);
	       	$valuesFormExport[] = new Oara_Curl_Parameter('linkingmethod', '1');
	       	$valuesFormExport[] = new Oara_Curl_Parameter('groups', 'All');
	       	$valuesFormExport[] = new Oara_Curl_Parameter('linktype', '1');
	       	$valuesFormExport[] = new Oara_Curl_Parameter('linkname', '468x60');
	       	
	        $urls[] = new Oara_Curl_Request('http://www.affiliatewindow.com/affiliates/get_code_all.php?', $valuesFormExport);
    	}
    	
    	$exportReport = $this->_exportClient->get($urls);
 
    	for ($i = 0; $i < count($exportReport); $i++){
    		$merchant = $merchantList[$i];
    		//$creativesMap[$merchant['cid']][] = '<a href="http://www.awin1.com/awclick.php?mid='.$merchant['cid'].'&id='.$this->_affiliateId.'" target="_blank">'.$merchant['name'].'</a>';
			
    		if (preg_match_all("/<!--START MERCHANT:([^']+?)<!--END MERCHANT:/", $exportReport[$i], $matches)){
    			foreach ($matches[0] as $creative){
    				if (preg_match("/<a href=\"http:\/\/www\.awin1\.com\/cread\.php\?s=(.*)?&v=(.*)?&q=(.*)?&r=(.*)?\"><img/", $creative, $creativeMatch)){
    					
    					$size = getimagesize("http://www.awin1.com/cshow.php?s=".$creativeMatch[1]."&v=".$creativeMatch[2]."&q=".$creativeMatch[3]."&r=".$creativeMatch[4]);
    					
    					$creativeObject = new stdClass();
    					$creativeObject->sizeWidth = null;
						$creativeObject->sizeHeight = null;
						
    					if ($size){
    						$creativeObject->sizeWidth = $size[0];
							$creativeObject->sizeHeight = $size[1];
    					} else {
    						echo "Problem reading image\n\n";
    					}
						
						$creativeObject->id = $creativeMatch[1];
    					$creativeObject->snippet = $creative.'-->';
    					
    					$creativesMap[$merchant['cid']][] = $creativeObject;
    				}
    			}
    		}
    	}
    	
		return $creativesMap;
	}
}