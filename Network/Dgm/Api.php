<?php
/**
 * Api Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Dgm
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_Dgm_Api extends Oara_Network_Base{
    /**
     * Soap client.
     */
	private $_apiClient = null;
	/**
     * Curl client.
     */
	private $_curlClient = null;
	/**
     * Merchant Campaigns
     * @var array
     */
    private $_advertisersCampaings = null;
	/**
     * Export Merchants Parameters
     * @var array
     */
    private $_exportMerchantParameters = null;
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
     * Overview Check Array
     * @var array
     */
    private $_overviewCheckArray = null;
    /**
     * Marchant Array
     * @var array
     */
    private $_merchantList = array();
    
	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array ('advertiserid'=>'cid',
                                                      'advertisername'=>'name'
	                                                  );
	                                                  
	/**
     * page Size.
     */
	private $_pageSize = 25;
	
    /**
     * Constructor.
     * @param $dgm
     * @return Oara_Network_Dgm_Api
     */
	public function __construct($dgm, $groupId, $mode)
	{
        $configuration = $dgm->AffiliateNetworkConfig->toArray();
        //Reading the different parameters.
        $user = Oara_Utilities::arrayFetchValue($configuration,'key','user');
        $user = Oara_Utilities::decodePassword($user['value']);
        $password = Oara_Utilities::arrayFetchValue($configuration,'key','password');
        $password = Oara_Utilities::decodePassword($password['value']);
        
        
        $wsdlUrl = 'http://api.dgm-uk.com/Publisher.v1.1/publisher.wsdl';
        //Setting the apiClient.
		$this->_apiClient = new Zend_Soap_Client($wsdlUrl, array('login' => $user,
		                                                      'password' => $password,
		                                                      'encoding' => 'UTF-8',
														      'compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                                      'soap_version' => SOAP_1_1));

		$loginUrl = 'http://www.dgmpro.com/index.cfm';
		
		$valuesLogin = array(new Oara_Curl_Parameter('login', $user),
                             new Oara_Curl_Parameter('password', $password)
                             );
		
		$this->_curlClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $dgm, $groupId, $mode);
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://www.dgmpro.com/affiliates/index.cfm', array());

        $memberId = null;
        $result = $this->_curlClient->get($urls);
        if (preg_match("/<input type=\"hidden\" name=\"CompanyID\" value=\"(.*)\">/", $result[0], $matches)){
            $memberId = trim($matches[1]);
        } else{
        	throw new Exception('No member id found');
        }
        
		
		$this->_exportMerchantParameters = array('username' => $user,
                                                 'password' => $password,
                                                 'approvaltype' => 'approved'
                                                 );              
                                                 
                                                 
        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('pageCount', 100),
                                                 	new Oara_Curl_Parameter('startRow', 1),
                                                 	new Oara_Curl_Parameter('selectBy', 'Sale_Date'),
                                                 	new Oara_Curl_Parameter('orderBy', 'sale_date'),
                                                 	new Oara_Curl_Parameter('orderDirection', 'desc'),
                                                 	new Oara_Curl_Parameter('saleType', 'All'),
                                                 	new Oara_Curl_Parameter('country_ID', '0'),
                                                 	new Oara_Curl_Parameter('measures', 'sale_id,action_campaign_name,campaign_name,sale_date,Commission,sale_value,sale_origin,sale_status,referrer,sale_basket,system_lock_down,advertiserid'),
                                                 	new Oara_Curl_Parameter('creativeType', 'All'),
                                                 	new Oara_Curl_Parameter('saleStatus', '2,1,4,3'),
                                                 	new Oara_Curl_Parameter('companyID', $memberId)
                                                 	);
                                                 	      	
                                                 	
        $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('viewBy', 'DATEDAILY'),
        										 new Oara_Curl_Parameter('pageCount', 100),
                                                 new Oara_Curl_Parameter('startRow', 1),
                                                 new Oara_Curl_Parameter('orderBy', 'view_column'),
                                                 new Oara_Curl_Parameter('orderDirection', 'asc'),
                                                 new Oara_Curl_Parameter('measures', 'Impressions,Clicks,CTR,EPC,Sale_Count,Sale_Value,Commission'),
                                                 new Oara_Curl_Parameter('companyID', $memberId)
                                                 );
                                                 
        $this->_overviewCheckArray = array(
                                          'click_number',
                                          'impression_number',
                                          'transaction_number',
                                          'transaction_confirmed_value',
                                          'transaction_confirmed_commission',
                                          'transaction_pending_value',
                                          'transaction_pending_commission',
                                          'transaction_declined_value',
                                          'transaction_declined_commission'
                                          );
        
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = true;
		return $connection;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
     */
	public function getMerchantList($merchantMap = array())
	{
		
		$this->_advertisersCampaings = array();
		$merchantsImport = $this->_apiClient->GetCampaigns($this->_exportMerchantParameters);
		if (!isset($merchantsImport->campaigns) || !isset($merchantsImport->campaigns->campaign)){
			sleep(60);
			throw new Exception('Error advertisers not found');
		}
		$this->merchantList = Oara_Utilities::soapConverter($merchantsImport->campaigns->campaign, $this->_merchantConverterConfiguration);
		
		foreach ($merchantsImport->campaigns->campaign as $campaing){
			if (!isset($this->_advertisersCampaings[$campaing->advertiserid])){
				$this->_advertisersCampaings[$campaing->advertiserid] = $campaing->campaignid;
			} else {
				$this->_advertisersCampaings[$campaing->advertiserid] .= ','.$campaing->campaignid;
			}
			
		}
		
		return $this->merchantList;
	}
    
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
	{	
		$totalTransactions = Array();
		
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
        $valuesFromExport[] = new Oara_Curl_Parameter('campaignID', self::getAdvertisersCampaigns($merchantList));
        $valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("dd/MM/yyyy"));
        $valuesFromExport[] = new Oara_Curl_Parameter('toDate', $dEndDate->toString("dd/MM/yyyy"));
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://www.dgmpro.com/affiliates/cfc/dgmreports.cfc?method=getSalesCSV', $valuesFromExport);
        $exportReport = $this->_curlClient->post($urls);
        $reportUrl = null;
        if (preg_match("/<filelocation>(.*)<\/filelocation>/", $exportReport[0], $matches)){
            $reportUrl = trim($matches[1]);
        } else{
        	throw new Exception('No transaction report found');
        }
        $urls = array();
        $urls[] = new Oara_Curl_Request($reportUrl, array());
        $exportReport = $this->_curlClient->get($urls);
        $exportData = str_getcsv($exportReport[0],"\n");
        if (count($exportData) > 11){
		
			$iteration = self::calculeIterationNumber(count($merchantList), $this->_pageSize);
			for($it = 0; $it < $iteration; $it++){
				$merchantStartIndex = $this->_pageSize * $it;
				if (count($merchantList) > $this->_pageSize * ($it+1)){
					$merchantEndIndex = $this->_pageSize * ($it+1);
				} else {
					$merchantEndIndex = count($merchantList);
				}
				
				$urls = array();
				for ($merchantIndex = $merchantStartIndex;$merchantIndex < $merchantEndIndex;$merchantIndex++){
					
			        $valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
			        $valuesFromExport[] = new Oara_Curl_Parameter('campaignID', self::getAdvertisersCampaigns(array($merchantList[$merchantIndex])));
			        $valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("dd/MM/yyyy"));
			        $valuesFromExport[] = new Oara_Curl_Parameter('toDate', $dEndDate->toString("dd/MM/yyyy"));
			        
			        $urls[] = new Oara_Curl_Request('http://www.dgmpro.com/affiliates/cfc/dgmreports.cfc?method=getSalesCSV', $valuesFromExport);
				}
		        $exportReportUrl = $this->_curlClient->post($urls);
				$urls = array();
		        for ($i = 0; $i < count($exportReportUrl); $i++){
		        	$reportUrl = null;
			        if (preg_match("/<filelocation>(.*)<\/filelocation>/", $exportReportUrl[$i], $matches)){
			            $reportUrl = trim($matches[1]);
			            $urls[] = new Oara_Curl_Request($reportUrl, array());
			        } else{
			        	throw new Exception('No transaction report found');
			        }
		        }
		        $exportReports = $this->_curlClient->get($urls);
		        for ($i = 0; $i < count($exportReports); $i++){
		        	
		        	$exportData = str_getcsv($exportReports[$i],"\n");
		        
			        if (count($exportData) > 11){
			        	for($j = 9; $j < count($exportData) - 2 ; $j++){
				        	$transactionExportArray = str_getcsv($exportData[$j],",");
				        	if (count($transactionExportArray) == 23){
				        		$transaction = Array();
				        		$transaction['merchantId'] = $merchantList[$i];
				                $transactionDate =  new Zend_Date($transactionExportArray[4], 'dd-MMM-yyyy HH:mm:ss', 'en_GB');
				                $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				                        
				                $transaction['program'] = $transactionExportArray[2];
				                //$transactionExportArray[16]
				                $transaction['website'] = ''; 
				                //$transactionExportArray[7]
				                $transaction['link'] = '';
				                    
				                if ($transactionExportArray[15]=='Approved'){
				                    $transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				                } else if ($transactionExportArray[15]=='Pending'){
				                    $transaction['status'] = Oara_Utilities::STATUS_PENDING;
				                } else if ($transactionExportArray[15]=='Deleted'){
				                    $transaction['status'] = Oara_Utilities::STATUS_DECLINED;
				                }
				                        
				                $transaction['amount'] = $transactionExportArray[13];  
				                        
				                $transaction['commission'] = $transactionExportArray[12];
				                $totalTransactions[] = $transaction;
				        	}
			        	}
		        	}
		        }
			}
        }
        return $totalTransactions;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
     */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
	{
		$totalOverview = Array();
		
        $valuesFromExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
        $valuesFromExport[] = new Oara_Curl_Parameter('campaignID', self::getAdvertisersCampaigns($merchantList));
        $valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("dd/MM/yyyy"));
        $valuesFromExport[] = new Oara_Curl_Parameter('toDate', $dEndDate->toString("dd/MM/yyyy"));
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://www.dgmpro.com/affiliates/cfc/dgmreports.cfc?method=getSummaryCSV', $valuesFromExport);
        $exportReport = $this->_curlClient->post($urls);
        $reportUrl = null;
        if (preg_match("/<filelocation>(.*)<\/filelocation>/", $exportReport[0], $matches)){
            $reportUrl = trim($matches[1]);
        } else{
        	throw new Exception('No transaction report found');
        }
        $urls = array();
        $urls[] = new Oara_Curl_Request($reportUrl, array());
        $exportReport = $this->_curlClient->get($urls);
        $exportData = str_getcsv($exportReport[0],"\n");
        if (self::checkOverview($exportData)){
        	$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
        	
        	
	        $iteration = self::calculeIterationNumber(count($merchantList), $this->_pageSize);
			for($it = 0; $it < $iteration; $it++){
				$merchantStartIndex = $this->_pageSize * $it;
				if (count($merchantList) > $this->_pageSize * ($it+1)){
					$merchantEndIndex = $this->_pageSize * ($it+1);
				} else {
					$merchantEndIndex = count($merchantList);
				}
				
				$urls = array();
				for ($merchantIndex = $merchantStartIndex;$merchantIndex < $merchantEndIndex;$merchantIndex++){
	        		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
			        $valuesFromExport[] = new Oara_Curl_Parameter('campaignID', self::getAdvertisersCampaigns(array($merchantList[$merchantIndex])));
			        $valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("dd/MM/yyyy"));
			        $valuesFromExport[] = new Oara_Curl_Parameter('toDate', $dEndDate->toString("dd/MM/yyyy"));
			        $urls[] = new Oara_Curl_Request('http://www.dgmpro.com/affiliates/cfc/dgmreports.cfc?method=getSummaryCSV', $valuesFromExport);
	        	}
	        	$exportReportUrl = $this->_curlClient->post($urls);
	        	$urls = array();
	        	for ($i = 0; $i < count($exportReportUrl); $i++){
		        	$reportUrl = null;
			        if (preg_match("/<filelocation>(.*)<\/filelocation>/", $exportReportUrl[$i], $matches)){
			            $reportUrl = trim($matches[1]);
			            $urls[] = new Oara_Curl_Request($reportUrl, array());
			        } else{
			        	throw new Exception('No transaction report found');
			        }
	        	}
		        $overviewRegisters = $this->_curlClient->get($urls);
	        	for ($i = 0; $i < count($overviewRegisters); $i++){
					$exportData = str_getcsv($overviewRegisters[$i],"\n");
		        	for ($j = 7; $j < count($exportData) - 2 ; $j++){
		            	$overviewExportArray = str_getcsv($exportData[$j],",");
		            	
			            $overview = Array();
		                $overview['merchantId'] = $merchantList[$i]; 
			            $transactionDate =  new Zend_Date($overviewExportArray[0], 'dd-MMM-yyyy HH:mm:ss', 'en_GB');
		                $overview['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
		                    
		                $overview['link'] = '';
		                $overview['website'] = '';
		                $overview['click_number'] = (int)$overviewExportArray[2];
		                $overview['impression_number'] = (int)$overviewExportArray[1];
		                $overview['transaction_number'] = 0;
		                $overview['transaction_confirmed_value'] = 0;
		                $overview['transaction_confirmed_commission']= 0;
		                $overview['transaction_pending_value']= 0;
		                $overview['transaction_pending_commission']= 0;
		                $overview['transaction_declined_value']= 0;
		                $overview['transaction_declined_commission']= 0;
		                $transactionDateArray = Oara_Utilities::getDayFromArray($overview['merchantId'],$transactionArray, $transactionDate);
		                foreach ($transactionDateArray as $transaction){ 	
		                	$overview['transaction_number'] ++;
		                    if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
		                        $overview['transaction_confirmed_value'] += $transaction['amount'];
		                        $overview['transaction_confirmed_commission'] += $transaction['commission'];
		                    } else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
		                        $overview['transaction_pending_value'] += $transaction['amount'];
		                        $overview['transaction_pending_commission'] += $transaction['commission'];
		                    } else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
		                        $overview['transaction_declined_value'] += $transaction['amount'];
		                        $overview['transaction_declined_commission'] += $transaction['commission'];
		                    }
		                }
		                if (Oara_Utilities::checkRegister($overview)){
		                   $totalOverview[] = $overview;
		                }
		        	}
	        	}
			}
        }
        
		return $totalOverview;
	}
	
	private function getAdvertisersCampaigns($merchantList){
		$advertiserCampaigns = '';
		foreach ($merchantList as $idMerchant){
			if ($advertiserCampaigns == ''){
				$advertiserCampaigns .= $this->_advertisersCampaings[$idMerchant];
			} else {
				$advertiserCampaigns .= ','.$this->_advertisersCampaings[$idMerchant];
			}
		}
		return $advertiserCampaigns;
	}
	
	/**
	 * Check the overview
	 * @param array $exportData
	 * @return boolean
	 */
    private function checkOverview($exportData){
    	$result = false;
    	$num = count($exportData)-2;
        $j = 7;
        while ($j < $num && !$result) {
        	$overviewExportArray = str_getcsv($exportData[$j],",");
        	$result = self::checkOverviewRegister($overviewExportArray);
        	$j++;
        }
        
        return $result;
    	
    }
	/**
     * Check If the register has interesting information
     * @param array $register
     * @param array $properties
     * @return boolean
     */
    public static function checkOverviewRegister(array $register){
    	$ok = false;
    	$i = 1;
    	while ($i < count($register) && !$ok){
    		if ($register[$i] != 0 && $register[$i] != '-'){
    			$ok = true;
    		}
    		$i++;
    	}
    	return $ok;
    }
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	
    	$urls = array();
    	$parameters = array();
    	$parameters[] = new Oara_Curl_Parameter('fuseaction', 'newreports.payment_summary');
    	
        $urls[] = new Oara_Curl_Request('http://www.dgmpro.com/affiliates/index.cfm?', $parameters);
        $exportReport = $this->_curlClient->get($urls);
    	
		/*** load the html into the object ***/
	    $doc = new DOMDocument();
	    libxml_use_internal_errors(true);
	    $doc->validateOnParse = true;
	    $doc->loadHTML($exportReport[0]);
	    $tableList = $doc->getElementsByTagName('table');
		$registerTable = $tableList->item(9);
    	if ($registerTable == null){
			throw new Exception ('Fail getting the payment History');
		}
		
		$registerLines = $registerTable->childNodes;
		for ($i = 2;$i < $registerLines->length - 1;$i++) {
			$registerLine = $registerLines->item($i)->childNodes;
			$obj = array();
			$date = new Zend_Date($registerLine->item(0)->nodeValue, "dd-MMM-yyyy", 'en_US');
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
			$obj['pid'] = $date->get(Zend_Date::DAY).$date->get(Zend_Date::MONTH).$date->get(Zend_Date::YEAR);
			
			$obj['value'] = 0;
			$obj['method'] = '';
			$value = substr($registerLine->item(10)->nodeValue, 2);
			if ( $value != 0){
				$obj['value'] += Oara_Utilities::parseDouble($value);
				$obj['method'] .= 'CHEQUE';
			}
			$value = substr($registerLine->item(8)->nodeValue, 2);
			if ( $value != 0){
				$obj['value'] += Oara_Utilities::parseDouble($value);
				if ($obj['method'] != ''){
					$obj['method'] .= '/BACS';
				} else {
					$obj['method'] .= 'BACS';
				}
				
			}
			$paymentHistory[] = $obj;
		}
    	
    	return $paymentHistory;
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
	
}