<?php
/**
 * Export Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Por
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_Por_Api extends Oara_Network_Base{
    /**
     * Export client.
     * @var Oara_Curl_Access
     */
	private $_client = null;
	
	/**
	 * Merchant Export Parameters
	 * @var array
	 */
	private $_exportMerchantParameters = null;
	
	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	
	/**
	 * Api Password 
	 */
	private $_apiPassword = null;
	
	/**
	 * Session Id
	 */
	private $_sessionId = null;

	/**
	 * Constructor and Login
	 * @param $por
	 * @return Oara_Network_Por_Api
	 */
	public function __construct($por, $groupId, $mode)
	{
		$configuration = $por->AffiliateNetworkConfig->toArray();

		$user = Oara_Utilities::arrayFetchValue($configuration, 'key', 'user');
		$user = Oara_Utilities::decodePassword($user['value']);
		$password = Oara_Utilities::arrayFetchValue($configuration, 'key', 'password');
		$password = Oara_Utilities::decodePassword($password['value']);
		$apiPassword = Oara_Utilities::arrayFetchValue($configuration, 'key', 'apiPassword');
        $this->_apiPassword = Oara_Utilities::decodePassword($apiPassword['value']);

		$loginUrl = 'https://secure.paidonresults.com/cgi-bin/affiliate-login/login.pl';
		
		$valuesLogin = array(new Oara_Curl_Parameter('username', $user),
                             new Oara_Curl_Parameter('password', $password)
                             );
		
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $por, $groupId, $mode);
		
		$valuesFormExport = array(new Oara_Curl_Parameter('username', $user),
	                              new Oara_Curl_Parameter('password', $password)
	                             );
	                             
       	$urls = array();
        $urls[] = new Oara_Curl_Request($loginUrl, $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        if (!preg_match("/session=(.*)\"/", $exportReport[0], $matches)){
        	throw new Exception("No session found");
        }
        $this->_sessionId = $matches[1];
        
        $this->_exportMerchantParameters = array(new Oara_Curl_Parameter('apikey', $this->_apiPassword),
												 new Oara_Curl_Parameter('Format', 'CSV'),
												 new Oara_Curl_Parameter('FieldSeparator', 'comma'),
												 new Oara_Curl_Parameter('AffiliateID', $user),
												 new Oara_Curl_Parameter('MerchantCategories', 'ALL'),
												 new Oara_Curl_Parameter('Fields', 'MerchantID,MerchantName,MerchantURL'),
												 new Oara_Curl_Parameter('JoinedMerchants', 'YES'),
												 new Oara_Curl_Parameter('MerchantsNotJoined', 'NO'),
												);
		                                 
		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('apikey', $this->_apiPassword),
													new Oara_Curl_Parameter('Format', 'CSV'),
													new Oara_Curl_Parameter('FieldSeparator', 'comma'),
													new Oara_Curl_Parameter('AffiliateID', $user),
													new Oara_Curl_Parameter('Fields', 'MerchantID,OrderDate,HTTPReferal,CustomTrackingID,OrderValue,AffiliateCommission,TransactionType,CreativeName'),
													new Oara_Curl_Parameter('AffiliateID', $user),
													new Oara_Curl_Parameter('DateFormat', 'DD/MM/YYYY+HH:MN:SS'),
													new Oara_Curl_Parameter('PendingSales', 'YES'),
													new Oara_Curl_Parameter('ValidatedSales', 'YES'),
													new Oara_Curl_Parameter('VoidSales', 'YES'),
													new Oara_Curl_Parameter('GetNewSales', 'YES')
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
        $merchants = Array();
        
        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportMerchantParameters);
       	$urls = array();
        $urls[] = new Oara_Curl_Request('http://affiliate.paidonresults.com/api/merchant-directory?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $merchantExportArray = str_getcsv($exportData[$i], ",");
            $obj = Array();
	        $obj['cid'] = $merchantExportArray[0];
	        $obj['name'] = $merchantExportArray[1];
	        $obj['url'] = $merchantExportArray[2];
	        $merchants[] = $obj;
        }
        return $merchants;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
	public function getTransactionList($merchantList = null , Zend_Date $dStartDate = null , Zend_Date $dEndDate = null)
	{
		$totalTransactions = Array();
		
        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
       	$urls = array();
        $urls[] = new Oara_Curl_Request('http://affiliate.paidonresults.com/api/transactions?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $exportData = str_getcsv($exportReport[0], "\r\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
        	$transactionExportArray = str_getcsv($exportData[$i], ",");
			$transaction = array();
			$transaction['merchantId'] = $transactionExportArray[0];
			
			$transactionDate = new Zend_Date($transactionExportArray[1], "dd/MM/yyyy HH:mm:ss");
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
			
			//$transaction['link'] = $transactionExportArray[2];
			//$transaction['website'] = $transactionExportArray[3];
			$transaction['program'] = $transactionExportArray[7];
			
			$transaction['amount'] = (double) $transactionExportArray[4];
			$transaction['commission'] = (double) $transactionExportArray[5];
			
			if ($transactionExportArray[6] == 'VALIDATED'){
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
			} else if ($transactionExportArray[6] == 'PENDING'){
				$transaction['status'] = Oara_Utilities::STATUS_PENDING;
			} else if ($transactionExportArray[6] == 'VOID'){
				$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
			}
			
			$totalTransactions[] = $transaction;
		}
		
        return $totalTransactions;
        
	}
	
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
     */
    public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
        $totalOverviews = Array();
        $transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
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
                	}
        		}
                $totalOverviews[] = $overview;
        	}
        }
        
        return $totalOverviews;                                 	
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