<?php
/**
 * Api Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Zn
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_Zanox extends Oara_Network{
    /**
     * Soap client.
     */
	private $_apiClient = null;
	
	/**
     * page Size.
     */
	private $_pageSize = 50;
    
    /**
     * Constructor.
     * @param $affiliateWindow
     * @return Oara_Network_Zn_Api
     */
	public function __construct($credentials)
	{
		
		$api = Oara_Network_Zanox_Zapi_ApiClient::factory(PROTOCOL_SOAP, VERSION_2009_07_01);
 		
		$connectId = $credentials['connectId'];
        $secretKey = $credentials['secretKey'];
        $publicKey = $credentials['publicKey'];

       	$api->setConnectId($connectId);
       	$api->setSecretKey($secretKey);
       	$api->setPublicKey($publicKey);
 
       	$this->_apiClient = $api;
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = true;
		try{
			$profile = $this->_apiClient->getProfile();
		} catch (Exception $e){
			$connection = false;
		}
		
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array())
	{
		$merchantList = array();

		$adsList = $this->_apiClient->getAdspaces(0, $this->_pageSize);
		if ($adsList->total > 0){
			$iteration = self::calculeIterationNumber($adsList->total, $this->_pageSize);
			for($i = 0; $i < $iteration; $i++){
				$adsList = $this->_apiClient->getAdspaces($i, $this->_pageSize);
				foreach ($adsList->adspaceItems->adspaceItem as $ads){
					
					$adsMerchantList = $this->_apiClient->getProgramsByAdspace($ads->id, 0, $this->_pageSize);
					if ($adsMerchantList->total > 0){
						$iterationMerchantList = self::calculeIterationNumber($adsMerchantList->total, $this->_pageSize);
						for($j = 0; $j < $iterationMerchantList; $j++){
							$adsMerchantList = $this->_apiClient->getProgramsByAdspace($ads->id, $j, $this->_pageSize);
							foreach ($adsMerchantList->programItems->programItem as $adsMerchant){
								if (!isset($merchantList[$adsMerchant->id])){
									$obj = array();
									$obj['cid'] = $adsMerchant->id;
									$obj['name'] = $adsMerchant->name;
									$obj['url'] = $adsMerchant->url;
									$obj['description'] = $adsMerchant->descriptionLocal;
									$merchantList[$adsMerchant->id] = $obj;
								}
							}
						}
					}
				}
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

		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		foreach ($dateArray as $date){
			$totalAuxTransactions = array();
			echo round(memory_get_usage(true)/1048576,2)." megabytes 1\n\n"; 
			$transactionList = $this->_apiClient->getSales( $date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, 0, $this->_pageSize);			
			if ($transactionList->total > 0){
				$iteration = self::calculeIterationNumber($transactionList->total, $this->_pageSize);
				for($i = 0; $i < $iteration; $i++){
					$transactionList = $this->_apiClient->getSales( $date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, $i, $this->_pageSize);
					$totalAuxTransactions = array_merge($totalAuxTransactions, $transactionList->saleItems->saleItem);
					unset($transactionList);
					gc_collect_cycles();
				}
				
			}
			$leadList = $this->_apiClient->getLeads( $date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, 0, $this->_pageSize);
			if ($leadList->total > 0){
				$iteration = self::calculeIterationNumber($leadList->total, $this->_pageSize);
				for($i = 0; $i < $iteration; $i++){
					$leadList = $this->_apiClient->getLeads( $date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, $i, $this->_pageSize);
					$totalAuxTransactions = array_merge($totalAuxTransactions, $leadList->leadItems->leadItem );
					unset($leadList);
					gc_collect_cycles();
				}
			}
			
			echo round(memory_get_usage(true)/1048576,2)." megabytes 2\n\n"; 
			foreach ($totalAuxTransactions as $transaction){
				
				if (in_array($transaction->program->id,$merchantList)){
					$obj = array();
					if ($transaction->reviewState == 'confirmed' || $transaction->reviewState == 'approved'){
						$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else if ($transaction->reviewState == 'open'){
						$obj['status'] = Oara_Utilities::STATUS_PENDING;
					} else if ($transaction->reviewState == 'rejected'){
						$obj['status'] = Oara_Utilities::STATUS_DECLINED;
					}
					if (!isset($transaction->amount) || $transaction->amount == 0){
						$obj['amount'] = $transaction->commission;
					} else {
						$obj['amount'] = $transaction->amount;
					}
					$obj['commission'] = $transaction->commission;
					$obj['date'] = $transaction->trackingDate;
					//$obj['link'] = $transaction->admedium->_;
					//$obj['linkId'] = $transaction->admedium->id;
					$obj['website'] = $transaction->adspace->_;
					$obj['websiteId'] = $transaction->adspace->id;
					$obj['merchantId'] = $transaction->program->id;
					$obj['program'] = $transaction->program->_;
					$totalTransactions[] = $obj;
				}
				
			}
			unset($totalAuxTransactions);
			gc_collect_cycles();
			echo round(memory_get_usage(true)/1048576,2)." megabytes 3\n\n"; 
		}
		return $totalTransactions;
	}
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
     */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
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
        $auxEndDate->addDay(1);

        $transactionArray = self::transactionMapPerDay($transactionList);
        
        $adsList = $this->_apiClient->getAdspaces(0, $this->_pageSize);
		if ($adsList->total > 0){
			//calculate number of iterations for the adsList
			$aIteration = self::calculeIterationNumber($adsList->total, $this->_pageSize);
			for($a = 0; $a < $aIteration; $a++){
				$adsList = $this->_apiClient->getAdspaces($a, $this->_pageSize);
				
				foreach ($adsList->adspaceItems->adspaceItem as $ads){
					
					$adsMerchantList = $this->_apiClient->getProgramsByAdspace($ads->id, 0, $this->_pageSize);
					if ($adsMerchantList->total > 0){
						$iterationMerchantList = self::calculeIterationNumber($adsMerchantList->total, $this->_pageSize);
						for($b = 0; $b < $iterationMerchantList; $b++){
							$adsMerchantList = $this->_apiClient->getProgramsByAdspace($ads->id, $b, $this->_pageSize);
							foreach ($adsMerchantList->programItems->programItem as $adsMerchant){
								if (in_array($adsMerchant->id, $merchantList)){
									echo $auxStartDate->toString("yyyy-MM-dd")." - ".$auxEndDate->toString("yyyy-MM-dd");
									$overviewList = $this->_apiClient->getReportBasic( $auxStartDate->toString("yyyy-MM-dd"), $auxEndDate->toString("yyyy-MM-dd"), 'trackingDate',
						        												   						   null, $adsMerchant->id, null, null, null, $ads->id, array('day'));			   
						        	if ($overviewList->total > 0){
										foreach ($overviewList->reportItems->reportItem as $overview){
											$obj = array();
											$obj['merchantId'] = $adsMerchant->id;
											$overviewDate = new Zend_Date($overview->day,"yyyy-MM-dd");
											$obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
											                	
											$obj['website'] = $ads->name;
											$obj['websiteId'] = $ads->id;
											//$obj['link'] = $adMedia->name;
											//$obj['linkId'] = $adMedia->id;
											                            
											$obj['impression_number'] = $overview->viewCount;
											$obj['click_number'] = $overview->clickCount;
											$obj['transaction_number'] = 0;
											                            
											$obj['transaction_confirmed_commission'] = 0;
											$obj['transaction_confirmed_value'] = 0;
											$obj['transaction_pending_commission'] = 0;
											$obj['transaction_pending_value'] = 0;
											$obj['transaction_declined_commission'] = 0;
											$obj['transaction_declined_value'] = 0;
											$transactionDateArray = self::getDayFromArray($obj['merchantId'], $obj['websiteId'], $transactionArray, $overviewDate);
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
												$totalOverview[] = $obj;
											}
										}
						        	}
						        	unset($overviewList);
									gc_collect_cycles();
								}
								echo count($totalOverview)."\n\n";
								echo round(memory_get_usage(true)/1048576,2)." megabytes \n\n";
							}
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
    	$paymentList = $this->_apiClient->getPayments(0, $this->_pageSize);
    	
    	if ($paymentList->total > 0){
    		$iteration = self::calculeIterationNumber($paymentList->total, $this->_pageSize);
    		for($j = 0; $j < $iteration; $j++){
				$paymentList = $this->_apiClient->getPayments($j, $this->_pageSize);
	    		foreach ($paymentList->paymentItems->paymentItem as $payment){
	    			$obj = array();
	    			$paymentDate = new Zend_Date($payment->createDate, "yyyy-MM-ddTHH:mm:ss");
	    			$obj['method'] = 'BACS';
	    			$obj['pid'] = $paymentDate->toString("yyyyMMddHHmmss");
	    			$obj['value'] = $payment->amount;
	    			
	    			
	    			$obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");	
	    			
	    			$paymentHistory[] = $obj;
	    		}
    		}
    	}
    	return $paymentHistory;
    }
    

	/**
     * Filter the transactionList per day
     * @param array $transactionList
     * @return array
     */
    public function transactionMapPerDay(array $transactionList){
    	$transactionMap = array();
    	foreach ($transactionList as $transaction){
    		$dateString = substr($transaction['date'], 0, 10);
    		if (!isset($transactionMap[$transaction['merchantId']][$transaction['websiteId']][$dateString])){
    			$transactionMap[$transaction['merchantId']][$transaction['websiteId']][$dateString] = array();
    		}
            
    		$transactionMap[$transaction['merchantId']][$transaction['websiteId']][$dateString][] = $transaction;
    	}
    	
    	return $transactionMap;
    }
	/**
	 * Get the day for this transaction array
	 * @param map $dateArray
	 * @param Zend_Date $date
	 * @return array
	 */
	public function getDayFromArray($merchantId, $websiteId, $dateArray, Zend_Date $date){
		$resultArray = array();
		if (isset($dateArray[$merchantId][$websiteId])){
			$dateString = $date->toString("yyyy-MM-dd");
			if (isset($dateArray[$merchantId][$websiteId][$dateString])){
				$resultArray = $dateArray[$merchantId][$websiteId][$dateString];
			}
		}
		return $resultArray;
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