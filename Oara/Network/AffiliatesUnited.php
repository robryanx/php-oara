<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_AffiliatesUnited
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_AffiliatesUnited extends Oara_Network{
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
     * Export Payment Parameters
     * @var array
     */
	private $_exportPaymentParameters = null;
	/**
	 * Merchants by name
	 */
	private $_merchantMap = array();
    /**
     * Client 
     * @var unknown_type
     */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Daisycon
	 */
	public function __construct($credentials)
	{
		$user = $credentials['user'];
        $password = $credentials['password'];       
		
		$valuesLogin = array(
							 new Oara_Curl_Parameter('username', $user),
							 new Oara_Curl_Parameter('password', $password),
							 new Oara_Curl_Parameter('fromUrl', 'http://www.affutd.com/'),
							 new Oara_Curl_Parameter('hubpage', 'y')
							 );
		
		$loginUrl = 'https://www.affutd.com/en//login/submit';		 
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		

        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('periods', 'custom'),
	                                                new Oara_Curl_Parameter('minDate', '{"year":"2008","month":"01","day":"01"}'),
	                                                new Oara_Curl_Parameter('show_periods', '1'),
	                                                new Oara_Curl_Parameter('product', ''),
	                                                new Oara_Curl_Parameter('profile', ''),
	                                                new Oara_Curl_Parameter('ts_type', 'advertiser'),
	                                                new Oara_Curl_Parameter('reportFirst', 'product'),
	                                                new Oara_Curl_Parameter('reportSecond', 'date'),
	                                                new Oara_Curl_Parameter('reportThird', ''),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'realDownloads'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'tlrAmount'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'sportRFDCount'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'sportUniqueRealPlayers'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'bingoUniqueRealPlayers'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'bingoRFDCount'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'realClicks'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'realImpressions'),
	                                                new Oara_Curl_Parameter('csvRequested', 'EXPORT AS CSV')
                                                   );
                                                                                             
       $this->_exportOverviewParameters =  array(new Oara_Curl_Parameter('periods', 'custom'),
	                                                new Oara_Curl_Parameter('minDate', '{"year":"2008","month":"01","day":"01"}'),
	                                                new Oara_Curl_Parameter('show_periods', '1'),
	                                                new Oara_Curl_Parameter('product', ''),
	                                                new Oara_Curl_Parameter('profile', ''),
	                                                new Oara_Curl_Parameter('ts_type', 'advertiser'),
	                                                new Oara_Curl_Parameter('reportFirst', 'product'),
	                                                new Oara_Curl_Parameter('reportSecond', 'date'),
	                                                new Oara_Curl_Parameter('reportThird', ''),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'realDownloads'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'tlrAmount'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'sportRFDCount'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'sportUniqueRealPlayers'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'bingoUniqueRealPlayers'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'bingoRFDCount'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'realClicks'),
	                                                new Oara_Curl_Parameter('columns%5B%5D', 'realImpressions'),
	                                                new Oara_Curl_Parameter('csvRequested', 'EXPORT AS CSV')
                                                   );
                                               
       $this->_exportPaymentParameters = array(); 
       
                                               
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		//If not login properly the construct launch an exception
 		$connection = false;
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.affutd.com/en/', array());
		$exportReport = $this->_client->get($urls);
		
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('li .cplLogout');
		if (count($results) > 0){
			$connection = true;
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
        $urls[] = new Oara_Curl_Request('https://www.affutd.com/en/traffic-stats/advertiser', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$merchantList = $dom->query('#product');
		$merchantList = $merchantList->current();
    	if ($merchantList != null){
		    $merchantLines = $merchantList->childNodes;
			for ($i = 0;$i < $merchantLines->length;$i++) {
				if ($merchantLines->item($i)->getAttribute("value") != ""){
					$obj = array();
			        $obj['cid'] = $merchantLines->item($i)->getAttribute("value");
			        $obj['name'] = $merchantLines->item($i)->getAttribute("label");
			        $merchants[] = $obj;
				}
			}
	    }
	    $obj = array();
        $obj['cid'] = 1;
        $obj['name'] = "William Hill";
        $merchants[] = $obj;
		$this->_merchantMap = $merchantMap;
		foreach ($merchants as $merchant){
			if (!isset($this->_merchantMap[$merchant['name']])){
				$this->_merchantMap[$merchant['name']] = $merchant['cid'];
			}
		}
        
        
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
		
		$totalTransactions = array();

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('fromPeriod', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('toPeriod', $dEndDate->toString("yyyy-MM-dd"));
		
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.affutd.com/en/traffic-stats/advertiser', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$campaignOption = $dom->query('option[label="DEFAULT"]');
		$campaignOption = $campaignOption->current();
		$valuesFromExport[] = new Oara_Curl_Parameter('campaign', $campaignOption->getAttribute('value'));
		
      	$hiddenParam = $dom->query('#jsonCampaigns');
      	$hiddenParam = $hiddenParam->current();
		$valuesFromExport[] = new Oara_Curl_Parameter($hiddenParam->getAttribute('id'), $hiddenParam->getAttribute('value'));

		$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.affutd.com/en/traffic-stats/advertiser', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
        $exportData = str_getcsv($exportReport[0],"\n");
        $num = count($exportData);
        for ($i = 1; $i < $num-1; $i++) {
        	$transactionExportArray = str_getcsv($exportData[$i],",");
        	if (Oara_Utilities::parseDouble($transactionExportArray[3]) != 0 &&
        		isset($this->_merchantMap[$transactionExportArray[0]]) &&
        		in_array($this->_merchantMap[$transactionExportArray[0]], $merchantList)){
        			
	            $transaction = Array();
	            $transaction['merchantId'] = $this->_merchantMap[$transactionExportArray[0]];
	            $transactionDate = new Zend_Date($transactionExportArray[1], 'yyyy-MM-dd', 'en');
	            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
	            
	            $transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
	            $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[3]);
	            $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[3]);
	            $totalTransactions[] = $transaction;
        	}
        }
        
        return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
		$overviewArray = Array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('fromPeriod', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('toPeriod', $dEndDate->toString("yyyy-MM-dd"));
		
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.affutd.com/en/traffic-stats/advertiser', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$campaignOption = $dom->query('option[label="DEFAULT"]');
		$campaignOption = $campaignOption->current();
		$valuesFromExport[] = new Oara_Curl_Parameter('campaign', $campaignOption->getAttribute('value'));
		
      	$hiddenParam = $dom->query('#jsonCampaigns');
      	$hiddenParam = $hiddenParam->current();
		$valuesFromExport[] = new Oara_Curl_Parameter($hiddenParam->getAttribute('id'), $hiddenParam->getAttribute('value'));

		$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.affutd.com/en/traffic-stats/advertiser', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
        $exportData = str_getcsv($exportReport[0],"\n");
        $num = count($exportData);
        for ($i = 1; $i < $num-1; $i++) {
        	$overviewExportArray = str_getcsv($exportData[$i],",");
        	if (isset($this->_merchantMap[$overviewExportArray[0]]) &&
        		in_array($this->_merchantMap[$overviewExportArray[0]], $merchantList)){
	            
        		$overview = Array();
                $overview['merchantId'] = $this->_merchantMap[$overviewExportArray[0]];
	            $overviewDate =  new Zend_Date($overviewExportArray[1], 'yyyy-MM-dd', 'en');
                $overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
                
                $overview['click_number'] = (int)$overviewExportArray[8];
                $overview['impression_number'] = (int)$overviewExportArray[9];
                $overview['transaction_number'] = 0;
                $overview['transaction_confirmed_value'] = 0;
                $overview['transaction_confirmed_commission']= 0;
                $overview['transaction_pending_value']= 0;
                $overview['transaction_pending_commission']= 0;
                $overview['transaction_declined_value']= 0;
                $overview['transaction_declined_commission']= 0;
                $overview['transaction_paid_value']= 0;
                $overview['transaction_paid_commission']= 0;
                $transactionDateArray = Oara_Utilities::getDayFromArray($overview['merchantId'],$transactionArray, $overviewDate, true);
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
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_PAID){
                        $overview['transaction_paid_value'] += $transaction['amount'];
                        $overview['transaction_paid_commission'] += $transaction['commission'];
                    }
                }
                if (Oara_Utilities::checkRegister($overview)){
                   $overviewArray[] = $overview;
                }
        	}
        }
        
		foreach ($transactionArray as $merchantId => $merchantTransaction){
        	foreach ($merchantTransaction as $date => $transactionList){
        		
        		$overview = Array();
                                    
                $overview['merchantId'] = $merchantId;
                $overviewDate = new Zend_Date($date, "yyyy-MM-dd");
                $overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
                $overview['click_number'] = 0;
                $overview['impression_number'] = 0;
                $overview['transaction_number'] = 0;
                $overview['transaction_confirmed_value'] = 0;
                $overview['transaction_confirmed_commission']= 0;
                $overview['transaction_pending_value']= 0;
                $overview['transaction_pending_commission']= 0;
                $overview['transaction_declined_value']= 0;
                $overview['transaction_declined_commission']= 0;
                $overview['transaction_paid_value']= 0;
                $overview['transaction_paid_commission']= 0;
                foreach ($transactionList as $transaction){
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
                	} else if ($transaction['status'] == Oara_Utilities::STATUS_PAID){
                    	$overview['transaction_paid_value'] += $transaction['amount'];
                    	$overview['transaction_paid_commission'] += $transaction['commission'];
                	}
        		}
                $overviewArray[] = $overview;
        	}
        }
	 			
		return $overviewArray;
	}

}