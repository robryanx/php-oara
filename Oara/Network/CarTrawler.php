<?php
/**
 * Export Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Ct
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_CarTrawler extends Oara_Network{
    /**
     * Export client.
     * @var Oara_Curl_Access
     */
	private $_client = null;
	
	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;

	/**
	 * Constructor and Login
	 * @param $cartrawler
	 * @return Oara_Network_Ct_Export
	 */
	public function __construct($credentials)
	{
		
		$user = $credentials['user'];
        $password = $credentials['password'];
		
		$loginUrl = 'https://www.cartrawler.com/partner/affiliates2.asp?Action=Validate';
		
		$valuesLogin = array(new Oara_Curl_Parameter('UserID', $user),
                             new Oara_Curl_Parameter('Pin', $password)
                             );
		
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		                                 
		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('datetype', 'res'),
													new Oara_Curl_Parameter('searchdate.x', '47'),
													new Oara_Curl_Parameter('searchdate.y', '13'),
													new Oara_Curl_Parameter('SubAccount', '0'),
													new Oara_Curl_Parameter('strsearch', '')
													);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.cartrawler.com/affengine/home2.asp', array());
        $exportReport = $this->_client->get($urls);
		if (!preg_match("/Your session has timed out/", $exportReport[0], $matches)){
			$connection = true;
		}
		return $connection;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
     */
	public function getMerchantList($merchantMap = array())
	{
        $merchants = Array();
        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'Cartrawler';
        $obj['url'] = 'https://www.cartrawler.com/';
        $merchants[] = $obj;
        
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
        $valuesFormExport[] = new Oara_Curl_Parameter('start_year', $dStartDate->toString("yyyy"));
        $valuesFormExport[] = new Oara_Curl_Parameter('start_month', $dStartDate->toString("M"));
        $valuesFormExport[] = new Oara_Curl_Parameter('start_day', $dStartDate->toString("d"));
        $valuesFormExport[] = new Oara_Curl_Parameter('end_year', $dEndDate->toString("yyyy"));
        $valuesFormExport[] = new Oara_Curl_Parameter('end_month', $dEndDate->toString("M"));
        $valuesFormExport[] = new Oara_Curl_Parameter('end_day', $dEndDate->toString("d"));
       	$urls = array();
        $urls[] = new Oara_Curl_Request('https://www.cartrawler.com/affengine/AFFxreservelist.asp?action=update', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
	    $exportTransactionList = self::readTransactionTable($exportReport[0], $dStartDate,$dEndDate);
		foreach ($exportTransactionList as $exportTransaction){
			$transaction = array();
			$exportTransaction = str_getcsv($exportTransaction,";");
			$transaction['merchantId'] = 1;
			
			$stamp = strtotime($exportTransaction[2]);
			$transaction['date'] = date("Y-m-d H:i:s", $stamp);
			$transaction['amount'] = (double) $exportTransaction[11];
			$transaction['commission'] = (double) $exportTransaction[13];
			if ($exportTransaction[14] == 'CONFIRMED'){
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
			} else if ($exportTransaction[14] == 'CANCELLED'){
				$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
			} else if ($exportTransaction[14] == 'UNCONFIRMED' || $exportTransaction[14] =='REBOOKED' || $exportTransaction[14] =='PENDING INVOICE'){
				$transaction['status'] = Oara_Utilities::STATUS_PENDING;
			} else{
				throw new Exception("New status found ".$transaction['status']);
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
     * Read the html table in the report
     * @param string $htmlReport
     * @param Zend_Date $startDate
     * @param Zend_Date $endDate
     * @param int $iteration
     * @return array:
     */
    public function readTransactionTable($htmlReport, Zend_Date $startDate, Zend_Date $endDate, $iteration = 0){
    	$transactions = array();
    	$dom = new Zend_Dom_Query($htmlReport);
	    $results = $dom->query('#reportingtable');
		$count = count($results);
    	if ($count == 1){
			$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
    		for ($j = 1; $j < count($exportData); $j++){
    			$transactions[] = $exportData[$j];
    		}
		    
		    if (preg_match("/<a href=\"(.*)\">\|Next\|<\/a>/",$htmlReport, $matches)){
		    	$iteration++;
		    	$urls = array();
		    	$parameters = array();
		    	$parameters[] = new Oara_Curl_Parameter('action', 'update');
		    	$parameters[] = new Oara_Curl_Parameter('Recid', ($iteration*100)+1);
		    	$parameters[] = new Oara_Curl_Parameter('rangestart', $startDate->toString("yyyy-MM-dd"));
		    	$parameters[] = new Oara_Curl_Parameter('rangeend', $endDate->toString("yyyy-MM-dd"));
		    	$parameters[] = new Oara_Curl_Parameter('datetype', 'res');
		    	$parameters[] = new Oara_Curl_Parameter('strsearch', '');
		    	$parameters[] = new Oara_Curl_Parameter('confirm', '');
		    	$parameters[] = new Oara_Curl_Parameter('dated', '');
		    	$parameters[] = new Oara_Curl_Parameter('sort', 'resdate');
		    	$parameters[] = new Oara_Curl_Parameter('order', '');
		    	$parameters[] = new Oara_Curl_Parameter('subaccount', '');
		    	$urls[] = new Oara_Curl_Request('https://www.cartrawler.com/affengine/AFFxreservelist.asp?', $parameters);
		        $exportReport = $this->_client->get($urls);
		    	$transactions = array_merge($transactions, self::readTransactionTable($exportReport[0], $startDate, $endDate, $iteration));
		    }
    	}
    	return $transactions;
    }
    
    /**
     * 
     * Function that Convert from a table to Csv
     * @param unknown_type $html
     */
    private function htmlToCsv($html){
    	$html = str_replace(array("\t","\r","\n"), "", $html);
    	$csv = "";
    	$dom = new Zend_Dom_Query($html);
      	$results = $dom->query('tr');
      	$count = count($results); // get number of matches: 4
      	foreach ($results as $result){
      		$tdList = $result->childNodes;
      		$tdNumber = $tdList->length;
			for ($i = 0;$i < $tdNumber;$i++) {
				$value = (String)$tdList->item($i)->nodeValue;
				if (strlen(trim($value)) > 0){
					if ($i != $tdNumber -1){
						$csv .= trim($value).";";
					} else {
						$csv .= trim($value);
					}
				}
			}
			$csv .= "\n";
      	}
    	$exportData = str_getcsv($csv,"\n");
    	return $exportData;
    }
    /**
     * 
     * Function that returns the innet HTML code 
     * @param unknown_type $element
     */
	private function DOMinnerHTML($element)
	{
	    $innerHTML = "";
	    $children = $element->childNodes;
	    foreach ($children as $child)
	    {
	        $tmp_dom = new DOMDocument();
	        $tmp_dom->appendChild($tmp_dom->importNode($child, true));
	        $innerHTML.=trim($tmp_dom->saveHTML());
	    }
	    return $innerHTML;
	}

}