<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_ClickBank
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_ClickBank extends Oara_Network{
    /**
     * Api Key
     * @var string
     */
    private $_api = null;
    /**
     * Dev Key
     * @var string
     */
    private $_dev = null;
    
    /**
     * Merchant List
     * @var array
     */
    private $_merchantList = null;
    /**
     * Constructor and Login
     * @param $credentials
     * @return Oara_Network_Effiliation
     */
    public function __construct($credentials)
    {
    	
        $user = $credentials["user"];
        $password = $credentials["password"];
        $loginUrl = "https://".$user.".accounts.clickbank.com/account/login?";
        
		$valuesLogin = array(new Oara_Curl_Parameter('destination', "/account/mainMenu.htm"),
							 new Oara_Curl_Parameter('nick', $user),
                             new Oara_Curl_Parameter('pass', $password),
                             new Oara_Curl_Parameter('login', "Log In"),
                             new Oara_Curl_Parameter('rememberMe', "true"),
                             new Oara_Curl_Parameter('j_username', $user),
                             new Oara_Curl_Parameter('j_password', $password)
                             );

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

        $urls = array();
        $urls[] = new Oara_Curl_Request("https://".$user.".accounts.clickbank.com/account/profile.htm", array());
        $result = $this->_client->get($urls);
        if (preg_match_all("/(API-(.*)?)\s</", $result[0], $matches)){
        	$this->_api = $matches[1][0];
        }
    	if (preg_match_all("/(DEV-(.*)?)</", $result[0], $matches)){
        	$this->_dev = $matches[1][0];
        }
        
    }
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		if ($this->_api != null && $this->_dev != null){
			$connection = true;
		}
		return $connection;
	}

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
     */
    public function getMerchantList(){
    	$merchants = array();
    	$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "ClickBank";
		$obj['url'] = "www.clickbank.com";
		$merchants[] = $obj;
        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
    	$totalTransactions = array();
    	$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
    	$number = self::returnApiData("https://api.clickbank.com/rest/1.2/orders/count?startDate=".$dStartDate->toString("yyyy-MM-dd")."&endDate=".$dEndDate->toString("yyyy-MM-dd"));
        
    	if ($number[0] != 0){ 
    		$transactionXMLList = self::returnApiData("https://api.clickbank.com/rest/1.2/orders/list?startDate=".$dStartDate->toString("yyyy-MM-dd")."&endDate=".$dEndDate->toString("yyyy-MM-dd"));
    		foreach ($transactionXMLList as $transactionXML){
	    		$transactionXML = simplexml_load_string($transactionXML, null, LIBXML_NOERROR | LIBXML_NOWARNING);
	    	    
	    		foreach ($transactionXML->orderData as $singleTransaction){
	    			
		            $transaction = Array();
					$transaction['merchantId'] = 1;
					$transactionDate =  new Zend_Date(self::findAttribute($singleTransaction, 'date'),'yyyy-MM-ddTHH:mm:ss');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					unset ($transactionDate);
					 
					if (self::findAttribute($singleTransaction, 'affi') != null){
						$transaction['custom_id'] = self::findAttribute($singleTransaction, 'affi');
					}
		
					$transaction['unique_id'] = self::findAttribute($singleTransaction, 'receipt');
		
					$transaction['amount'] = (double)$filter->filter(self::findAttribute($singleTransaction, 'amount'));
					$transaction['commission'] = (double)$filter->filter(self::findAttribute($singleTransaction, 'amount'));
					
					//if (self::findAttribute($singleTransaction, 'txnType') == 'RFND'){
					//	$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					//} else {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					//}
					 
					
					$totalTransactions[] = $transaction;
	            }
    			
    		}
    		
    	}
    	
	   
        return $totalTransactions;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
     */
    public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
        $overviewArray = array();
        
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
	/**
	 * 
	 * Api connection to ClickBank
	 * @param unknown_type $xmlLocation
	 * @throws Exception
	 */
	private function returnApiData($xmlLocation){
		$dataArray = array();
		// Get the data
		$httpCode = 206;
		$page = 1;
		while ($httpCode != 200){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $xmlLocation);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch,CURLOPT_HTTPHEADER,array("Page: $page", "Accept: application/xml","Authorization: ".$this->_dev.":".$this->_api));

			$dataArray[] = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($httpCode != 200 && $httpCode != 206){
				throw new Exception("Couldn't connect to the API");
			}
			//Close Curl session
			curl_close($ch);
			$page++;
		}
		
		return $dataArray;
		
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
}