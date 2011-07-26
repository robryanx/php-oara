<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Ls
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_LinkShare extends Oara_Network{
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
     * Website Array
     * @var array
     */
    private $_websiteList = array();
    /**
     * Merchant Array
     * @var array
     */
    private $_linkList = array();
    /**
     * Client 
     * @var unknown_type
     */
    private $_client = null;
    /**
     * Member id 
     * @var int
     */
    private $_memberId = null;
    /**
     * Nid for this Linkshare instance
     * @var string
     */
    private $_nid = null;
    /**
     * Constructor and Login
     * @param $ls
     * @return Oara_Network_Ls_Export
     */
    public function __construct($credentials)
    {
        
        $user = $credentials['user'];
        $password = $credentials['password'];
        //Choosing the Linkshare network
    	 if ($credentials['network'] == 'uk'){
        	$this->_nid = '3';
        } else if ($credentials['network'] == 'us'){
        	$this->_nid = '1';
        } else if ($credentials['network'] == 'eu'){
        	$this->_nid = '31';
        }
        
        
        $loginUrl = 'https://cli.linksynergy.com/cli/common/authenticateUser.php';

        $valuesLogin = array(new Oara_Curl_Parameter('front_url', ''),
                             new Oara_Curl_Parameter('postLoginDestination', ''),
                             new Oara_Curl_Parameter('loginUsername', $user),
                             new Oara_Curl_Parameter('loginPassword', $password),
                             new Oara_Curl_Parameter('x', '66'),
                             new Oara_Curl_Parameter('y', '11')
                            );
                            

        $this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		
        
       
                              
        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('analyticchannel', 'Reports'),
        											new Oara_Curl_Parameter('analyticpage', 'Advance Reports'),
        											new Oara_Curl_Parameter('dateRangeData', '1'),
        											new Oara_Curl_Parameter('reportType', '7'),
        											new Oara_Curl_Parameter('dateRange', 'fromTo'),
        											new Oara_Curl_Parameter('advMID', '-1'),
        											new Oara_Curl_Parameter('nid', $this->_nid),
        											new Oara_Curl_Parameter('x', '86'),
                                                    new Oara_Curl_Parameter('y', '12')
                                                   );
                                                 
        $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('analyticchannel', 'Reports'),
                                                new Oara_Curl_Parameter('analyticpage', 'Advance Reports'),
                                                new Oara_Curl_Parameter('dateRangeData', '1'),
                                                new Oara_Curl_Parameter('reportType', '4'),
                                                new Oara_Curl_Parameter('dateRange', 'fromTo'),
                                                new Oara_Curl_Parameter('advMID', '-1'),
                                                new Oara_Curl_Parameter('nid', $this->_nid),
                                                new Oara_Curl_Parameter('x', '71'),
                                                new Oara_Curl_Parameter('y', '18')
                                               );


        
        $this->_exportPaymentParameters = array(new Oara_Curl_Parameter('startRow', '0'),
                                                 new Oara_Curl_Parameter('sortKey', ''),
                                                 new Oara_Curl_Parameter('sortOrder', ''),
                                                 new Oara_Curl_Parameter('format', '6'),
                                                 new Oara_Curl_Parameter('button', 'Go')
                                                 );
        
    }
	/**
	 * 
	 * Format Csv
	 * @param unknown_type $csv
	 */
	private function formatCsv($csv){
		//$csv = preg_replace('/(?<!,)"(?!,)/', '', $csv); 
		//$csv = preg_replace('/(?<!"),/', '', $csv);
		//$csv = preg_replace('/(?<!")\n/', '', $csv); 
		
		$csv = preg_replace("/\"\"/","", $csv);
		preg_match_all("/,\"([^\"]+?)\"/", $csv, $matches);
        foreach ($matches[1] as $match){
        	if (preg_match("/,/", $match)){
        		$rep = preg_replace("/,/","", $match);
        		$csv = str_replace($match, $rep, $csv);
        		$match = $rep;
        	}
        	if (preg_match("/\n/", $match)){
        		$rep = preg_replace("/\n/","", $match);
        		$csv = str_replace($match, $rep, $csv);
        	}
        }
		return $csv;
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = true;
		
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://cli.linksynergy.com/cli/publisher/home.php', array());
        $result = $this->_client->get($urls);
        
        if (!preg_match("/https:\/\/cli\.linksynergy\.com\/cli\/common\/logout\.php/", $result[0], $matches)){
            $connection = false;
            
        }
		return $connection;
	}

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
     */
    public function getMerchantList($merchantMap = array()){
        $merchants = array();
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://cli.linksynergy.com/cli/publisher/programs/carDownload.php', array());
        $result = $this->_client->get($urls);
        
        $exportData = str_getcsv(self::formatCsv($result[0]),"\n");
        $num = count($exportData);
        
    	
        for ($i = 1; $i < $num; $i++) {
        	$merchantArray = str_getcsv($exportData[$i],",");
        	//if (($this->_nid == '3' && $merchantArray[10] == 'U.K.')||
        	//	($this->_nid == '1' && $merchantArray[14] == 'US')){
        		
	            $obj = Array();
	            
	            if (!isset($merchantArray[2])){
	            	throw new Exception("Error getting merchants");
	            }
	            
				$obj['cid'] = (int)$merchantArray[2];		
				$obj['name'] = $merchantArray[0];
				$obj['description'] = $merchantArray[3];
				$obj['url'] = $merchantArray[1];
				$merchants[] = $obj;
        	//}
              
        }
        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($idMerchant, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
        $totalTransactions = Array();

        $valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
        $valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("M/d/yyyy"));
        $valuesFromExport[] = new Oara_Curl_Parameter('toDate', $dEndDate->toString("M/d/yyyy"));

        $urls = array();
        $urls[] = new Oara_Curl_Request('https://cli.linksynergy.com/cli/publisher/reports/advancedReports.php?', $valuesFromExport);
        $exportReport = $this->_client->post($urls);
        
   		$exportReportNumber = count($exportReport);
	    for ($i = 0; $i < $exportReportNumber; $i++){
	        $doc = new DOMDocument();
		    libxml_use_internal_errors(true);
		    $doc->validateOnParse = true;
			$doc->loadHTML($exportReport[$i]); 
			$frame = $doc->getElementById('frame');
			
			if ($frame !== null){
				$frameUrl = null;
		 		foreach ($frame->attributes as $attrName => $attrNode){
		 			if ($attrName == 'src'){
		 				$frameUrl = $attrNode->nodeValue;
		 			}
		 		}
		 		
		 		$urls = array();
		        $urls[] = new Oara_Curl_Request($frameUrl, array());
		        $exportReport = $this->_client->get($urls);
		 		while (!preg_match("/No Results/", $exportReport[0], $matches)){
			 		if (preg_match("/<a class=\"NQWMenuItem\" name=\"SectionElements\" href=\"javascript:void\(null\);\" onclick=\"NQWClearActiveMenu\(\);Download\('([^<]*)'\); return false\">Download Data<\/a>/", $exportReport[0], $matches)){
			    		$totalTransactions = self::getTransactions($matches, $totalTransactions, $merchantList);
			    		break;
					} else {
						if (preg_match("/result=\"searching\"/", $exportReport[0], $matches)){
							$urls = array();
					        $urls[] = new Oara_Curl_Request($frameUrl, array());
					        $exportReport = $this->_client->get($urls);
						} else {
							throw new Exception("Error getting transactions");
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
    public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
        $totalOverview = Array();
        
        $mothOverviewUrls = array();
        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
        $valuesFormExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("M/d/yyyy"));
        $valuesFormExport[] = new Oara_Curl_Parameter('toDate', $dEndDate->toString("M/d/yyyy"));
        $mothOverviewUrls[] = new Oara_Curl_Request('https://cli.linksynergy.com/cli/publisher/reports/advancedReports.php', $valuesFormExport);
        $exportMothReport = $this->_client->post($mothOverviewUrls);
        $exportReportNumber = count($exportMothReport);
    	for ($i = 0; $i < $exportReportNumber; $i++){
	        $doc = new DOMDocument();
		    libxml_use_internal_errors(true);
		    $doc->validateOnParse = true;
			$doc->loadHTML($exportMothReport[$i]); 
			$frame = $doc->getElementById('frame');
		
			if ($frame !== null){
				$frameUrl = null;
		 		foreach ($frame->attributes as $attrName => $attrNode){
		 			if ($attrName == 'src'){
		 				$frameUrl = $attrNode->nodeValue;
		 			}
		 		}
		 		
				
		 		$urls = array();
		        $urls[] = new Oara_Curl_Request($frameUrl, array());
		        $exportReport = $this->_client->get($urls);
		        if (preg_match("/result=\"searching\"/", $exportReport[0], $matches)){
						$urls = array();
				        $urls[] = new Oara_Curl_Request($frameUrl, array());
				        $exportReport = $this->_client->get($urls);
		        }
		    	if (!preg_match("/<a class=\"NQWMenuItem\" name=\"SectionElements\" href=\"javascript:void\(null\);\" onclick=\"NQWClearActiveMenu\(\);Download\('([^<]*)'\); return false\">Download Data<\/a>/", $exportReport[0], $matches)){
		    		if (preg_match("/No Results/", $exportReport[0], $matches)){
						return $totalOverview;
					}
				}
			}
	    }
        
        
        
        $transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
        
        $mothOverviewUrls = array();
        $dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
        $dateArraySize = sizeof($dateArray);
        for ($i = 0; $i < $dateArraySize; $i++){
        	$valuesFormExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
            $valuesFormExport[] = new Oara_Curl_Parameter('fromDate', $dateArray[$i]->toString("M/d/yyyy"));
            $valuesFormExport[] = new Oara_Curl_Parameter('toDate', $dateArray[$i]->toString("M/d/yyyy"));
            $mothOverviewUrls[] = new Oara_Curl_Request('https://cli.linksynergy.com/cli/publisher/reports/advancedReports.php', $valuesFormExport);
        }
        $exportMothReport = $this->_client->post($mothOverviewUrls);
        $exportReportNumber = count($exportMothReport);
	    for ($i = 0; $i < $exportReportNumber; $i++){
	        $doc = new DOMDocument();
		    libxml_use_internal_errors(true);
		    $doc->validateOnParse = true;
			$doc->loadHTML($exportMothReport[$i]); 
			$frame = $doc->getElementById('frame');
		
			$frameUrl = null;
			if ($frame !== null){
		 		foreach ($frame->attributes as $attrName => $attrNode){
		 			if ($attrName == 'src'){
		 				$frameUrl = $attrNode->nodeValue;
		 			}
		 		}
		 		
				$urls = array();
		        $urls[] = new Oara_Curl_Request($frameUrl, array());
		        $exportReport = $this->_client->get($urls);
		 		while (!preg_match("/No Results/", $exportReport[0], $matches)){
			 		if (preg_match("/<a class=\"NQWMenuItem\" name=\"SectionElements\" href=\"javascript:void\(null\);\" onclick=\"NQWClearActiveMenu\(\);Download\('([^<]*)'\); return false\">Download Data<\/a>/", $exportReport[0], $matches)){
			    		$totalOverview = self::getOverview($matches, $totalOverview, $merchantList, $mothOverviewUrls[$i]->getParameter(9), $transactionArray);
			    		break;
					} else {
						if (preg_match("/result=\"searching\"/", $exportReport[0], $matches)){
							$urls = array();
					        $urls[] = new Oara_Curl_Request($frameUrl, array());
					        $exportReport = $this->_client->get($urls);
						} else {
							throw new Exception("Error getting transactions");
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
    	/**
    	$urls = array();
		$valuesFromExport = $this->_exportPaymentParameters;
        $urls[] = new Oara_Curl_Request('https://members.cj.com/member/'.$this->_memberId.'/publisher/getpublisherpaymenthistory.do?', $valuesFromExport);   
        $exportReport = $this->_client->get($urls);
        $exportData = str_getcsv($exportReport[0],"\n");
	    $num = count($exportData);
	    for ($j = 1; $j < $num; $j++) {
	    	$paymentData = str_getcsv($exportData[$j],",");
	    	$obj = array();
	    	$date = new Zend_Date($paymentData[0], "dd-MMM-yyyy HH:mm", 'en_US');
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
	    	$obj['value'] = Oara_Utilities::parseDouble($paymentData[1]);
	    	$obj['method'] = $paymentData[2];
	    	$obj['pid'] = $paymentData[6];
	    	$paymentHistory[] = $obj;
	    }
	    **/
    	return $paymentHistory;
    }
    /**
     * 
     * Get the transactions for this affiliates due that maybe we have to retrieve the data twice 
     * @param array $matches
     * @param array $totalTransactions
     */
    private function getTransactions($matches, $totalTransactions, $merchantList){
    	$matches[1] = preg_replace(
									array("/\\\\x25/","/&amp;/"),
									array('%', '&'),
									$matches[1]
								   );

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://analytics.linksynergy.com/SynergyAnalytics/'.$matches[1], array());
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv(iconv('UTF-16', 'UTF-8',$exportReport[0]),"\n");
		$num = count($exportData);
		$groupedTransactions = array();
		for ($j = 1; $j < $num; $j++) {
		  	$transactionData = str_getcsv($exportData[$j],"\t");
		  	
		   	if (in_array((int)$transactionData[3],$merchantList)){
		   		
		   		if (isset($groupedTransactions[$transactionData[0]])){
		   			$transaction = $groupedTransactions[$transactionData[0]];
		   		} else {
		   			$transaction = Array();
		   		}
				
	            $transaction['merchantId'] = (int)$transactionData[3];
	            $transactionDate = new Zend_Date($transactionData[1]." ".$transactionData[2], "MM/dd/yyyy HH:mm:ss");
	            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");  
		                
		        $transaction['program'] = $transactionData[6];
		        $transaction['website'] = '';
		        $transaction['link'] = '';
		       
		        $sales = Oara_Utilities::parseDouble($transactionData[7]);
		        
		        if ($sales != 0){
		           	$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
		        } else if ($sales == 0){
		            $transaction['status'] = Oara_Utilities::STATUS_PENDING;
		        } else if ($transactionData[6] == 'D'){
		            $transaction['status'] = Oara_Utilities::STATUS_DECLINED;
		        }
		                        
	            $transaction['amount'] = Oara_Utilities::parseDouble($transactionData[7]);   
		                       
		        $transaction['commission'] = Oara_Utilities::parseDouble($transactionData[9]);
		        
		        
		        if ((!isset($groupedTransactions[$transactionData[0]])) ||
		        	(isset($groupedTransactions[$transactionData[0]]) && $transaction['status'] != Oara_Utilities::STATUS_PENDING)){
		        	$groupedTransactions[$transactionData[0]] = $transaction;
		        }
		        
		        
		  	}
        }
        
        foreach ($groupedTransactions as $groupedTransaction){
        	$totalTransactions[] = $groupedTransaction;
        }
        
         
        return $totalTransactions;
    }
	/**
     * 
     * Get the overview for this affiliates due that maybe we have to retrieve the data twice 
     * @param array $matches
     * @param array $totalTransactions
     */
    private function getOverview($matches, $totalOverview, $merchantList, $parameter, $transactionArray){
    	$matches[1] = preg_replace(array("/\\\\x25/","/&amp;/"),
										   array('%', '&'),
										   $matches[1]
								  );
	    $overviewDate = $parameter->getValue();
        $overviewDate = new Zend_Date($overviewDate, "M/d/yyyy");						   
										   
										   
										   
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://analytics.linksynergy.com/SynergyAnalytics/'.$matches[1], array());
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv(iconv('UTF-16', 'UTF-8',$exportReport[0]),"\n");
		$num = count($exportData);
		for ($j = 1; $j < $num; $j++) {
			$overviewData = str_getcsv($exportData[$j],"\t");
			if (in_array((int)$overviewData[0],$merchantList)){
				 
				 $overview = Array();
	                    
	             $overview['merchantId'] = (int)$overviewData[0];
	             $overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
	             $overview['link'] = '';
	             $overview['website'] = '';
	             $overview['click_number'] = (int)$overviewData[3];
	             $overview['impression_number'] = (int)$overviewData[2];
	             $overview['transaction_number'] = 0;
	             $overview['transaction_confirmed_value'] = 0;
	             $overview['transaction_confirmed_commission']= 0;
	             $overview['transaction_pending_value']= 0;
	             $overview['transaction_pending_commission']= 0;
	             $overview['transaction_declined_value']= 0;
	             $overview['transaction_declined_commission']= 0;
				 $transactionDateArray =  Oara_Utilities::getDayFromArray($overview['merchantId'], $transactionArray, $overviewDate);
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
		return $totalOverview;
    }
}
