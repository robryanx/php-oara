<?php
/**
 * Api Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Tt
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_TradeTracker extends Oara_Network{
    /**
     * Soap client.
     */
	private $_apiClient = null;
    /**
     * Constructor.
     * @param $affiliateWindow
     * @return Oara_Network_Aw_Api
     */
	public function __construct($credentials)
	{
        $user = $credentials['user'];
        $password = $credentials['password'];
       
        
        $wsdlUrl = 'http://ws.tradetracker.com/soap/affiliate?wsdl';
        //Setting the client.
		$this->_apiClient = new Oara_Import_Soap_Client($wsdlUrl, array('encoding' => 'UTF-8',
				                                                        'compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
				                                                        'soap_version' => SOAP_1_1));
		
		$this->_apiClient->authenticate($user, $password, false, 'en_GB');
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
	public function getMerchantList(){
		$merchants = array();
		
		$merchantsAux = array();
		$options = array('assignmentStatus' => 'accepted');
		$affiliateSitesList = $this->_apiClient->getAffiliateSites();
		foreach ($affiliateSitesList as $affiliateSite){
			$campaignsList = $this->_apiClient->getCampaigns($affiliateSite->ID, $options);
			foreach ($campaignsList as $campaign){
				if (!isset($merchantsAux[$campaign->name])){
					$obj = Array();
			        $obj['cid'] = $campaign->ID;
			        $obj['name'] = $campaign->name;
			        $obj['url'] = $campaign->URL;
			        $merchantsAux[$campaign->name] = $obj;
				}
			}
		}
		foreach ($merchantsAux as $merchantAux){
			$merchants[] = $merchantAux;
		}
		
		return $merchants;
	}
   
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
     */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null)
	{	
		$totalTransactions = array();
		
		$options = array('registrationDateFrom' => $dStartDate->toString('yyyy-MM-dd'),
						 'registrationDateTo' => $dEndDate->toString('yyyy-MM-dd'),
						);
		$affiliateSitesList = $this->_apiClient->getAffiliateSites();
		foreach ($affiliateSitesList as $affiliateSite){
			foreach ($this->_apiClient->getConversionTransactions($affiliateSite->ID, $options) as $transaction) {
				if (in_array((int)$transaction->campaign->ID,$merchantList)){
					$object = array();
					
					$object['unique_id'] = $transaction->ID;
					
	                $object['merchantId'] = $transaction->campaign->ID;
	                $transactionDate =  new Zend_Date($transaction->registrationDate, "dd/MM/YY HH:mm:ss");
	                $object['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
	                
	                if ($transaction->reference != null){
	                	$object['custom_id'] = $transaction->reference;
	                }
	                
	                if ($transaction->transactionStatus == 'accepted'){
	                	$object['status'] = Oara_Utilities::STATUS_CONFIRMED;
	                } else if ($transaction->transactionStatus == 'pending'){
	                    $object['status'] = Oara_Utilities::STATUS_PENDING;
	                } else if ($transaction->transactionStatus == 'rejected'){
	                    $object['status'] = Oara_Utilities::STATUS_DECLINED;
	                }
	                
	                $object['amount'] = Oara_Utilities::parseDouble($transaction->orderAmount);
	                $object['commission'] = Oara_Utilities::parseDouble($transaction->commission);
	                $totalTransactions[] = $object;
				}
			}
		}
		
		return $totalTransactions;
	}
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
     */
	public function getOverviewList ($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null)
	{
		$totalOverview = Array();
		
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$affiliateSitesList = $this->_apiClient->getAffiliateSites();
        for ($i = 0; $i < sizeof($dateArray); $i++){
        	$auxStartDate = $dateArray[$i];
	        $options = array('dateFrom' => $auxStartDate->toString("yyyy-MM-dd"),
							 'dateTo' => $auxStartDate->toString("yyyy-MM-dd"),
							);
			foreach ($affiliateSitesList as $affiliateSite){
				foreach ($this->_apiClient->getReportCampaign($affiliateSite->ID, $options) as $report) {
					
					if (in_array((int)$report->campaign->ID,$merchantList)){
						
						$overview = Array();
	                    
	                    $overview['merchantId'] = $report->campaign->ID;
	                    $overview['date'] = $auxStartDate->toString("yyyy-MM-dd");
	                    
	                    
	                    $overview['click_number'] = (int)$report->reportData->overallClickCount;
	                   	$overview['impression_number'] = (int)$report->reportData->overallImpressionCount;
	                    $overview['transaction_number'] = 0;
	                    $overview['transaction_confirmed_value'] = 0;
	                    $overview['transaction_confirmed_commission']= 0;
	                    $overview['transaction_pending_value']= 0;
	                    $overview['transaction_pending_commission']= 0;
	                    $overview['transaction_declined_value']= 0;
	                    $overview['transaction_declined_commission']= 0;
	                    $overview['transaction_paid_value']= 0;
	                    $overview['transaction_paid_commission']= 0;
	                    $transactionDateArray = Oara_Utilities::getDayFromArray($report->campaign->ID,$transactionArray, $auxStartDate, true);
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
	                       $totalOverview[] = $overview;
	                   }
					}
				}
			}
        }
        
        
		//get the transactions that left
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
				$totalOverviews[] = $overview;
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
    	$options = array();
		//$options = array('billDateFrom' => '2009-01-01',
		//				   'billDateTo' => '2009-02-01',
		//				  );

		foreach ($this->_apiClient->getPayments($options) as $payment) {
			$obj = array();
			$date = new Zend_Date($payment->billDate, "dd/MM/yy");
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
			$obj['pid'] = $date->toString("yyyyMMdd");
			$obj['method'] = 'BACS';
			$obj['value'] = Oara_Utilities::parseDouble($payment->endTotal);
			$paymentHistory[] = $obj;
		}
    	return $paymentHistory;
    }
	
}