<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Daisycon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Daisycon extends Oara_Network{
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
        $merchantAuth = $credentials['merchantAuth'];
        $transactionAuth = $credentials['transactionAuth'];
        $overviewAuth = $credentials['overviewAuth'];

		$loginUrl = 'http://login.daisycon.com/en/index/';
        
		
		$valuesLogin = array(new Oara_Curl_Parameter('login[username]', $user),
							 new Oara_Curl_Parameter('login[password]', $password)
							);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		
		$this->_exportMerchantParameters = array(new Oara_Curl_Parameter('export', 'true'),
        										 new Oara_Curl_Parameter('username', $user),
                                                 new Oara_Curl_Parameter('auth', $merchantAuth),
                                                 new Oara_Curl_Parameter('filename', 'programs'),
                                                 new Oara_Curl_Parameter('filetype', 'csv'),
                                                 new Oara_Curl_Parameter('headers', 'true')
                                                 
                                                );
       
        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('export', 'true'),
                                                    new Oara_Curl_Parameter('username', $user),
                                                    new Oara_Curl_Parameter('auth', $transactionAuth),
	                                                new Oara_Curl_Parameter('filename', 'programs'),
	                                                new Oara_Curl_Parameter('filetype', 'csv'),
	                                                new Oara_Curl_Parameter('headers', 'true')
                                                   );
                                                                                           
       $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('export', 'true'),
                                                new Oara_Curl_Parameter('username', $user),
                                                new Oara_Curl_Parameter('auth', $overviewAuth),
                                                new Oara_Curl_Parameter('filename', 'programs'),
                                                new Oara_Curl_Parameter('filetype', 'csv'),
                                                new Oara_Curl_Parameter('headers', 'true')
                                               );
                                               
       $this->_exportPaymentParameters = array(); 
                                               
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		//If not login properly the construct launch an exception
		$connection = true;
		$valuesFromExport = $this->_exportMerchantParameters;
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/programs/myprograms/?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		
		$dom = new Zend_Dom_Query($exportReport[0]);
  
      	$results = $dom->query('#loginForm');
 
      	$count = count($results); // get number of matches: 4
		if($count > 0){
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
	
		$valuesFromExport = $this->_exportMerchantParameters;
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/programs/myprograms/?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		
		$exportData = str_getcsv($exportReport[0], "\r\n");
        $merchantReportList = Array();
        $num = count($exportData);
        for ($i = 2; $i < $num; $i++) {
            $merchantExportArray = str_getcsv($exportData[$i], ";");
            $obj = array();
            $obj['cid'] = $merchantExportArray[0];
            $obj['name'] = $merchantExportArray[1];
            $obj['url'] = $merchantExportArray[2];
            $merchants[] = $obj;
        }
        
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		
		$totalTransactions = array();
		
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('period', $dStartDate->toString("yyyyMMdd")."-".$dEndDate->toString("yyyyMMdd"));
    
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/stats/transactions/?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		
        $exportData = str_getcsv($exportReport[0],"\r\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],";");
            if (in_array((int)$transactionExportArray[10],$merchantList)){
	            $transaction = Array();
	            $merchantId = (int)$transactionExportArray[10];
	            $transaction['merchantId'] = $merchantId;
	            $transactionDate = new Zend_Date($transactionExportArray[2], 'MM-dd-yyyy HH:mm:ss');
	            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
	            $transaction['program'] = $transactionExportArray[4];
	            $transaction['link'] = '';
	            $transaction['website'] = '';
	            if ($transactionExportArray[5] == 'approved'){
	            	$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
	            } else if ($transactionExportArray[5] == 'pending' || $transactionExportArray[5] == 'potential' || $transactionExportArray[5] == 'open'){
	            	$transaction['status'] = Oara_Utilities::STATUS_PENDING;
	            } else if ($transactionExportArray[5] == 'disapproved'){
	                $transaction['status'] = Oara_Utilities::STATUS_DECLINED;
	            }
	            $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[16]);
	            $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[7]);
	            $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$overviewArray = Array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		foreach ($merchantList as $merchantId){

			$overviewExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
			$overviewExport[] = new Oara_Curl_Parameter('program', $merchantId);
			$overviewExport[] = new Oara_Curl_Parameter('period', $dStartDate->toString("yyyyMMdd")."-".$dEndDate->toString("yyyyMMdd"));
	
			$urls = array();
	        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/stats/month/?', $overviewExport);
			$exportReport = $this->_client->get($urls);
			
            $exportData = str_getcsv($exportReport[0],"\r\n");
            $num = count($exportData);
            $overviewDate = clone $dStartDate;
            $overviewDate->setHour(0);
            $overviewDate->setMinute(0);
            $overviewDate->setSecond(0);
            for ($j = 1; $j < $num; $j++) {
            	
                $overviewExportArray = str_getcsv($exportData[$j],";");
                
                $obj = array();
                $obj['merchantId'] = $merchantId;
                
                $overviewDate->setDay($overviewExportArray[0]);
                $obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
                            
                $obj['impression_number'] = 0;
                $obj['click_number'] = $overviewExportArray[1];
                $obj['transaction_number'] = 0;
                            
                $obj['transaction_confirmed_commission'] = 0;
                $obj['transaction_confirmed_value'] = 0;
                $obj['transaction_pending_commission'] = 0;
                $obj['transaction_pending_value'] = 0;
                $obj['transaction_declined_commission'] = 0;
                $obj['transaction_declined_value'] = 0;
                $transactionDateArray = Oara_Utilities::getDayFromArray($obj['merchantId'], $transactionArray, $overviewDate);
            	foreach ($transactionDateArray as $transaction){
                    $obj['transaction_number']++;
                    if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
                        $obj['transaction_confirmed_value'] += $transaction['amount'];
                        $obj['transaction_confirmed_commission'] += $transaction['commission'];
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
                        $obj['transaction_pending_value'] += $transaction['amount'];
                        $obj['transaction_pending_commission'] += $transaction['commission'];
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
                        $obj['transaction_declined_value'] += $transaction['amount'];
                        $obj['transaction_declined_commission'] += $transaction['commission'];
                    }
                }
                if (Oara_Utilities::checkRegister($obj)){
                	$overviewArray[] = $obj;
               	}
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