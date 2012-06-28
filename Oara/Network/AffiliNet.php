<?php
/**
 * Api Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_An
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_AffiliNet extends Oara_Network{
    /**
     * Soap client.
     */
	private $_client = null;
	/**
     * Soap token.
     */
	private $_token = null;
	/*
	 * User
	 */
	private $_user = null;
	/*
	 * User
	 */
	private $_password = null;
	
	/*
	 * PaymentHistory
	 */
	private $_paymentHistory = null;
	
	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array ('ProgramId'=>'cid',
                                                      'ProgramTitle'=>'name',
                                                      'Url'=>'url',
	                                                  'Description'=>'description'
	                                                  );
	/**
     * Converter configuration for the transactions.
     * @var array
     */
	private $_transactionConverterConfiguration = Array ('TransactionStatus'=>'status',
														 'TransactionId' => 'unique_id',
	                                                     'PublisherCommission'=>'commission',
		 												 'NetPrice'=>'amount',
	                                                     'RegistrationDate'=>'date',
													     'ProgramId'=>'merchantId',
														 'SubId'=>'custom_id'
	                                                    );
	                                                    
	/**
     * Converter configuration for the transactions for Payments.
     * @var array
     */
	private $_transactionPaymentsConverterConfiguration = Array ('TransactionStatus'=>'status',
																 'TransactionId' => 'unique_id',
			                                                     'PublisherCommission'=>'commission',
				 												 'NetPrice'=>'amount',
			                                                     'CheckDate'=>'date',
															     'ProgramId'=>'merchantId',
																 'SubId'=>'custom_id'
			                                                    );
    
    /**
     * Constructor.
     * @param $affilinet
     * @return Oara_Network_An_Api
     */
	public function __construct($credentials)
	{
        $this->_user = $credentials['user'];
        $this->_password = $credentials['password'];
        
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = true;
		self::Login();
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
	 */
	public function getMerchantList()
	{
		//Set the webservice
		$publisherProgramServiceUrl = 'https://api.affili.net/V2.0/PublisherProgram.svc?wsdl';
		$publisherProgramService = new Oara_Import_Soap_Client($publisherProgramServiceUrl, array('compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                     				  							   'soap_version' => SOAP_1_1));
		//Call the function
		$params = Array('Query' => '');
		$merchantList = self::affilinetCall('merchant', $publisherProgramService, $params);
		
		if ($merchantList->TotalRecords > 0){
			if ($merchantList->TotalRecords == 1){
				$merchant = $merchantList->Programs->ProgramSummary;
				$merchantList = array();
				$merchantList[] = $merchant;
				$merchantList = Oara_Utilities::soapConverter($merchantList, $this->_merchantConverterConfiguration);
			} else {
				$merchantList = $merchantList->Programs->ProgramSummary;
				$merchantList = Oara_Utilities::soapConverter($merchantList, $this->_merchantConverterConfiguration);
			}
		} else {
			$merchantList = array();
		}
		
		return $merchantList;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
     */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null)
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
		
        //Set the webservice      
		$publisherStatisticsServiceUrl = 'https://api.affili.net/V2.0/PublisherStatistics.svc?wsdl';
		$publisherStatisticsService = new Oara_Import_Soap_Client($publisherStatisticsServiceUrl, array('compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                     				  							   	  'soap_version' => SOAP_1_1));
        $iterationNumber = self::calculeIterationNumber(count($merchantList), 100);
        
        for ($currentIteration = 0; $currentIteration < $iterationNumber; $currentIteration++){
        	$merchantListSlice = array_slice($merchantList, 100*$currentIteration, 100);
	        $merchantListAux = array();
	        foreach ($merchantListSlice as $merchant){
	        	$merchantListAux[] = (string)$merchant;	
	        }
		
		
		
			//Call the function
			$params = array(
				            'StartDate' => strtotime($dStartDate->toString("yyyy-MM-dd")),
				            'EndDate' => strtotime($dEndDate->toString("yyyy-MM-dd")),
				            'TransactionStatus' => 'All',
				            'ProgramIds' => $merchantListAux,
			/*
				            'SubId' => '',
				            'ProgramTypes' => 'All',
				            'MaximumRecords' => '0',
				            'ValuationType' => 'DateOfRegistration'
				            */
				           );
			$currentPage = 1;	                      
			$transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);
			
    		while (isset($transactionList->TotalRecords) && $transactionList->TotalRecords > 0 && isset($transactionList->TransactionCollection->Transaction)){
				$transactionCollection = array();
    			if ($transactionList->TotalRecords == 1){
    				$transactionCollection [] = $transactionList->TransactionCollection->Transaction;
    			} else {
    				$transactionCollection =  $transactionList->TransactionCollection->Transaction;
    			}
    			
				$transactionList = Oara_Utilities::soapConverter($transactionCollection, $this->_transactionConverterConfiguration);

		        foreach ($transactionList as $transaction){
		        	//$transaction['merchantId'] = 3901;
		        	if ($transaction['status'] == 'Confirmed'){
		        		$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
		        	} else if ($transaction['status'] == 'Open'){
		        		$transaction['status'] = Oara_Utilities::STATUS_PENDING;
		        	} else if ($transaction['status'] == 'Cancelled'){
		        		$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
		        	}
		        	$totalTransactions[] = $transaction;
		        }
		        $currentPage++;
		        $transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);
        	}
        }
		
		return $totalTransactions;
	}
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
     */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null)
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

        //Set the webservice     
        $publisherStatisticsServiceUrl = 'https://api.affili.net/V2.0/PublisherStatistics.svc?wsdl';
		$publisherStatisticsService = new Oara_Import_Soap_Client($publisherStatisticsServiceUrl, array('compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                     				  							   	  	 'soap_version' => SOAP_1_1));
		
        $transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
        
        foreach($merchantList as $merchantId){
        	
        	//Call the function
        	$params = array(
					        'StartDate' => strtotime($auxStartDate->toString("yyyy-MM-dd")),
					        'EndDate' => strtotime($auxEndDate->toString("yyyy-MM-dd")),
					        'ProgramId' => (string)$merchantId,
					        'SubId' => '',
					        'ProgramTypes' => 'All',
					        'ValuationType' => 'DateOfRegistration'
					        );
					        
			$overviewList = self::affilinetCall('overview', $publisherStatisticsService, $params);		        
			
														          				 
        	if (isset($overviewList->DailyStatisticsRecords->DailyStatisticRecords->DailyStatisticsRecord) && !is_array($overviewList->DailyStatisticsRecords->DailyStatisticRecords->DailyStatisticsRecord)){
	        	$overviewList->DailyStatisticsRecords->DailyStatisticRecords->DailyStatisticsRecord = array($overviewList->DailyStatisticsRecords->DailyStatisticRecords->DailyStatisticsRecord);
       	 	}
       	 	if (isset($overviewList->DailyStatisticsRecords->DailyStatisticRecords->DailyStatisticsRecord)){

	        	foreach ($overviewList->DailyStatisticsRecords->DailyStatisticRecords->DailyStatisticsRecord as $overviewDay){
					$overview = array();
					$overview['date'] = $overviewDay->Date;
					$overview['merchantId'] = $merchantId;
					
					$overview['click_number'] = $overviewDay->PayPerClick->Clicks + $overviewDay->PayPerSaleLead->Clicks + $overviewDay->CombinedPrograms->Clicks;
					$overview['impression_number'] = $overviewDay->PayPerClick->Views + $overviewDay->PayPerSaleLead->Views + $overviewDay->CombinedPrograms->Views;
					
					
					
					$overview['transaction_confirmed_value'] = 0;
	        		$overview['transaction_pending_value'] = 0;
	        		$overview['transaction_declined_value'] = 0;
	        		$overview['transaction_confirmed_commission'] = 0;
	                $overview['transaction_pending_commission'] = 0;
	                $overview['transaction_declined_commission'] = 0;
	                $overview['transaction_paid_value']= 0;
					$overview['transaction_paid_commission']= 0;
	                
	                $overviewDate = new Zend_Date($overviewDay->Date, "dd-MM-yyyy HH:mm:ss");
					$transactionList = Oara_Utilities::getDayFromArray($merchantId, $transactionArray, $overviewDate, true);
					$overview['transaction_number'] = count($transactionList);
					foreach ($transactionList as $transaction){
					
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
				$totalOverview[] = $overview;
			}
		}
        
	    return $totalOverview;
	}
	/**
	 * Log in the API and get the data.
	 */
	public function Login(){
		$wsdlUrl = 'https://api.affili.net/V2.0/Logon.svc?wsdl';
        
        //Setting the client.
		$this->_client = new Oara_Import_Soap_Client($wsdlUrl, array('compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                                      'soap_version' => SOAP_1_1));
		$demoPublisherId   = 403233;  // one of the publisher IDs of our demo database
		$developerSettings = array('SandboxPublisherID' => $demoPublisherId);
        $this->_token = $this->_client->Logon(array(
										            'Username'  => $this->_user,
										            'Password'  => $this->_password,
										            'WebServiceType' => 'Publisher',
        											//'DeveloperSettings' => $developerSettings
										           ));
	    //echo "The token ". $this->_token ." expires:".$this->_client->GetIdentifierExpiration($this->_token)."\n\n";
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	//Set the webservice     
        
		//At first, we need to be sure that there are some data.
	    $auxStartDate = new Zend_Date("01-01-1990", "dd-MM-yyyy");
	    $auxStartDate->setHour("00");
        $auxStartDate->setMinute("00");
        $auxStartDate->setSecond("00");
        $auxEndDate = new Zend_Date();
    	$params = array(
    					'CredentialToken' => $this->_token,
    					'PublisherId' => $this->_user,
				        'StartDate' => strtotime($auxStartDate->toString("yyyy-MM-dd")),
				        'EndDate' => strtotime($auxEndDate->toString("yyyy-MM-dd")),
				       );
    	$accountServiceUrl = 'https://api.affili.net/V2.0/AccountService.svc?wsdl';	
		$accountService = new Oara_Import_Soap_Client($accountServiceUrl, array('compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                     				  			 'soap_version' => SOAP_1_1));
    	
    	$paymentList = self::affilinetCall('payment', $accountService, $params);
    	
		if (isset($paymentList->PaymentInformationCollection) && !is_array($paymentList->PaymentInformationCollection)){
	        $paymentList->PaymentInformationCollection = array($paymentList->PaymentInformationCollection);
        }
    	if (isset($paymentList->PaymentInformationCollection)){
    		foreach ($paymentList->PaymentInformationCollection as $payment){
    			$obj = array();
    			$obj['method'] = $payment->PaymentType;
    			$obj['pid'] = $payment->PaymentId;
    			$obj['value'] = $payment->GrossTotal;
    			$obj['date'] = $payment->PaymentDate;
    			$paymentHistory[] = $obj;
    		}
    	}
    	$this->_paymentHistory = $paymentHistory;
    	return $paymentHistory;
    }
    
    
    
	/**
	 *  It returns the transactions for a payment
	 * @see Oara_Network::paymentTransactions()
	 */
    public function paymentTransactions($paymentId, $merchantList, $startDate){
    	
   		$paymentTransactionList = array();

    	$paymentHistory = Oara_Utilities::registerBubbleSort($this->_paymentHistory);
    	
    	$paymentStartDate = new Zend_Date($startDate, "yyyy-MM-dd HH:mm:ss");
    	$paymentEndDate = null;
    	
    	$enc = false;
    	$i = 0;
    	$payment = null;
    	while(!$enc && $i < count($paymentHistory)){
    		$payment = $paymentHistory[$i];
    		if ($payment['pid'] == $paymentId) {
    			$enc = true;
    			$paymentEndDate = new Zend_Date($payment['date'], "yyyy-MM-dd HH:mm:ss");
    		} else {
    			$paymentStartDate = new Zend_Date($payment['date'], "yyyy-MM-dd HH:mm:ss");
    		}
    		$i++;
    	}
    	
    	if ($enc && $paymentStartDate->compare($paymentEndDate) <= 0){
	    	$totalTransactions = array();
	    	
	    	
	    	$dateArray = Oara_Utilities::monthsOfDifference(new Zend_Date($startDate, "yyyy-MM-dd HH:mm:ss"), $paymentEndDate);
			for ($i = 0; $i < count($dateArray); $i++){
				$monthStartDate = clone $dateArray[$i];
				$monthEndDate = null;
	
				if($i != count($dateArray)-1){
					$monthEndDate = clone $dateArray[$i];
					$monthEndDate->setDay(1);
					$monthEndDate->addMonth(1);
					$monthEndDate->subDay(1);
				} else {
					$monthEndDate = $paymentEndDate;
				}
				$monthEndDate->setHour(23);
				$monthEndDate->setMinute(59);
				$monthEndDate->setSecond(59);
	
				echo "\n importing from ".$monthStartDate->toString("dd-MM-yyyy HH:mm:ss"). " to ". $monthEndDate->toString("dd-MM-yyyy HH:mm:ss") ."\n";
	
				
		        //Set the webservice      
				$publisherStatisticsServiceUrl = 'https://api.affili.net/V2.0/PublisherStatistics.svc?wsdl';
				$publisherStatisticsService = new Oara_Import_Soap_Client($publisherStatisticsServiceUrl, array('compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
				                                     				  							   	  'soap_version' => SOAP_1_1));
		        $iterationNumber = self::calculeIterationNumber(count($merchantList), 100);
		        
		        for ($currentIteration = 0; $currentIteration < $iterationNumber; $currentIteration++){
		        	$merchantListSlice = array_slice($merchantList, 100*$currentIteration, 100);
			        $merchantListAux = array();
			        foreach ($merchantListSlice as $merchant){
			        	$merchantListAux[] = (string)$merchant;
			        }
				
					//Call the function
					$params = array(
						            'StartDate' => strtotime($monthStartDate->toString("yyyy-MM-dd")),
						            'EndDate' => strtotime($monthEndDate->toString("yyyy-MM-dd")),
						            'TransactionStatus' => 'Confirmed',
						            'ProgramIds' => $merchantListAux,
						           );
					$currentPage = 1;	                      
					$transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);
					
		    		while (isset($transactionList->TotalRecords) && $transactionList->TotalRecords > 0 && isset($transactionList->TransactionCollection->Transaction)){
						$transactionCollection = array();
		    			if ($transactionList->TotalRecords == 1){
		    				$transactionCollection [] = $transactionList->TransactionCollection->Transaction;
		    			} else {
		    				$transactionCollection =  $transactionList->TransactionCollection->Transaction;
		    			}
		    			
						$transactionList = Oara_Utilities::soapConverter($transactionCollection, $this->_transactionPaymentsConverterConfiguration);
		
				        foreach ($transactionList as $transaction){
				        	$transactionDate = new Zend_Date($transaction["date"], "yyyy-MM-dd HH:mm:ss");
				        	if ($paymentStartDate->compare($transactionDate) <= 0 && $paymentEndDate->compare($transactionDate) >= 0){
				        		$paymentTransactionList[] = $transaction["unique_id"];
				        	}
				        }
				        $currentPage++;
				        $transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);
		        	}
		        }
			}
    	}
    	
    	return $paymentTransactionList;
    }
    
    
    /**
     * Call to the API controlling the exception and Login
     */
    private function affilinetCall($call, $ws, $params, $try = 0, $currentPage = 0){
    	$result = null;
    	try{
    		
    		switch ($call){
	    		case 'merchant':
		        	$result = $ws->GetMyPrograms(array('CredentialToken' => $this->_token,
									           	  	 'GetProgramsRequestMessage' => $params));									        
		        break;
	    		case 'transaction':
	    			$pageSettings = array("CurrentPage"=>$currentPage, "PageSize" => 100);
	    			$result = $ws->GetTransactions(array('CredentialToken' => $this->_token,
									           	  	 'TransactionQuery' => $params,
	    											'PageSettings'=>$pageSettings));
	    		break;
		        case 'overview':
	    			$result = $ws->GetDailyStatistics(array('CredentialToken' => $this->_token,
								           					     'GetDailyStatisticsRequestMessage' => $params));	
	    		break;
	    		case 'payment':
	    			$result = $ws->GetPayments($params);	
	    		break;
		        default:
		        	throw new Exception ('No Affilinet Call available');
		        break;
    		}
    	}catch (Exception $e){
    		//checking if the token is valid
    		if (preg_match("/Login failed/", $e->getMessage()) && $try < 5){
            	self::Login();
            	$try++;
    			$result = self::affilinetCall($call, $ws, $params, $try, $currentPage);
            }else {
    			throw new Exception ("problem with Affilinet API, no login fault");
    		}
    	}
    	
    	return $result;
    	
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