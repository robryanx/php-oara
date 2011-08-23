<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Amazon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Amazon extends Oara_Network{
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
        $network = $credentials['network'];
        
		//Get html after Js
		$hiddenParams = self::getHiddenParamsAfterJs($credentials);
		
		$valuesLogin = array(
							 new Oara_Curl_Parameter('email', $user),
							 new Oara_Curl_Parameter('password', $password),
							 new Oara_Curl_Parameter('x', '33'),
							 new Oara_Curl_Parameter('y', '10')
							 );
		
      	foreach ($hiddenParams as $hiddenParamName => $hiddenParamValue){
      		$valuesLogin[] = new Oara_Curl_Parameter($hiddenParamName, $hiddenParamValue);
      	}
		
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliate-program.amazon.co.uk/gp/flex/sign-in/select.html?', $valuesLogin);
		$contentList = $this->_client->post($urls);
		
        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('tag', ''),
	                                                new Oara_Curl_Parameter('reportType', 'earningsReport'),
	                                                new Oara_Curl_Parameter('program', 'all'),
	                                                new Oara_Curl_Parameter('preSelectedPeriod', 'monthToDate'),
	                                                new Oara_Curl_Parameter('periodType', 'exact'),
	                                                new Oara_Curl_Parameter('submit.download_CSV.x', '106'),
	                                                new Oara_Curl_Parameter('submit.download_CSV.y', '11'),
	                                                new Oara_Curl_Parameter('submit.download_CSV', 'Download report (CSV)')
                                                   );
                                                                                       
       $this->_exportOverviewParameters =  array(new Oara_Curl_Parameter('tag', ''),
                                                 new Oara_Curl_Parameter('reportType', 'trendsReport'),
                                                 new Oara_Curl_Parameter('preSelectedPeriod', 'monthToDate'),
                                                 new Oara_Curl_Parameter('periodType', 'exact'),
                                                 new Oara_Curl_Parameter('submit.download_CSV.x', '106'),
                                                 new Oara_Curl_Parameter('submit.download_CSV.y', '11'),
                                                 new Oara_Curl_Parameter('submit.download_CSV', 'Download report (CSV)')
                                                );
                                               
       $this->_exportPaymentParameters = array(); 
                                               
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://affiliate-program.amazon.co.uk/gp/associates/network/main.html', array());
		$exportReport = $this->_client->get($urls);
		echo $exportReport[0];
		$dom = new Zend_Dom_Query($exportReport[0]);
  
      	$results = $dom->query('#identitybox');
 
      	$count = count($results);
		if($count == 0){
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
		
        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "Amazon";
        $obj['url'] = "www.amazon.com";
        $merchants[] = $obj;
        
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		
		$totalTransactions = array();
		
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('startDay', $dStartDate->toString("d"));
		$valuesFromExport[] = new Oara_Curl_Parameter('startMonth', (int)$dStartDate->toString("M")-1);
		$valuesFromExport[] = new Oara_Curl_Parameter('startYear', $dStartDate->toString("yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('endDay', $dEndDate->toString("d"));
		$valuesFromExport[] = new Oara_Curl_Parameter('endMonth', (int)$dEndDate->toString("M")-1);
		$valuesFromExport[] = new Oara_Curl_Parameter('endYear', $dEndDate->toString("yyyy"));
    
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://affiliate-program.amazon.co.uk/gp/associates/network/reports/report.html?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		
        $exportData = str_getcsv($exportReport[0],"\n");
        $num = count($exportData);
        for ($i = 2; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],"\t");
            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transactionDate = new Zend_Date($transactionExportArray[5], 'MMMM d,yyyy', 'en');
            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
            $transaction['program'] = $transactionExportArray[1];
            $transaction['link'] = "";
            $transaction['website'] = "";
            
            $transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
            $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[9]);
            $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[10]);
            $totalTransactions[] = $transaction;
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
			$overviewExport[] = new Oara_Curl_Parameter('startDay', $dStartDate->toString("d"));
			$overviewExport[] = new Oara_Curl_Parameter('startMonth', (int)$dStartDate->toString("M")-1);
			$overviewExport[] = new Oara_Curl_Parameter('startYear', $dStartDate->toString("yyyy"));
			$overviewExport[] = new Oara_Curl_Parameter('endDay', $dEndDate->toString("d"));
			$overviewExport[] = new Oara_Curl_Parameter('endMonth', (int)$dEndDate->toString("M")-1);
			$overviewExport[] = new Oara_Curl_Parameter('endYear', $dEndDate->toString("yyyy"));
			
			$urls = array();
	        $urls[] = new Oara_Curl_Request('https://affiliate-program.amazon.co.uk/gp/associates/network/reports/report.html?', $overviewExport);
			$exportReport = $this->_client->get($urls);
			$exportData = str_getcsv($exportReport[0],"\n");
            $num = count($exportData);
            for ($j = 2; $j < $num; $j++) {
            	
                $overviewExportArray = str_getcsv($exportData[$j],"\t");
                
                $obj = array();
                $obj['merchantId'] = 1;
                
                $overviewDate = new Zend_Date($overviewExportArray[0], "yyyy/MM/dd HH:mm:ss");
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
    	$urls = array();
        $urls[] = new Oara_Curl_Request('https://affiliate-program.amazon.co.uk/gp/associates/network/your-account/payment-history.html', array());
		$exportReport = $this->_client->get($urls);
		
    	$dom = new Zend_Dom_Query($exportReport[0]);
      	$results = $dom->query('.paymenthistory');
		$count = count($results);
		$yearArray = array();
		if ($count == 1){
			$paymentTable = $results->current();
			$paymentReport = self::htmlToCsv(self::DOMinnerHTML($paymentTable));
			for ($i = 2; $i < count($paymentReport) - 1; $i++){
				$paymentExportArray = str_getcsv($paymentReport[$i],";");
				
				$obj = array();
				$paymentDate = new Zend_Date($paymentExportArray[0], "M d yyyy", "en");
	    		$obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = $paymentDate->toString("yyyyMMdd");
				$obj['method'] = 'BACS';
				if (preg_match("/-/", $paymentExportArray[4]) && preg_match("/[0-9]*,?[0-9]*\.?[0-9]+/", $paymentExportArray[4], $matches)) {
					$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
					$paymentHistory[] = $obj;
				}
				
			}
		} else {
			throw new Exception('Problem getting the payments');
		}
    	return $paymentHistory;
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
				$value = $tdList->item($i)->nodeValue;
				if ($i != $tdNumber -1){
					$csv .= trim($value).";";
				} else {
					$csv .= trim($value);
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
	/**
	 * 
	 * Gets the cookies value for this network
	 * @param unknown_type $credentials
	 */
	private function readCookies($credentials){
		$dir = realpath(dirname(__FILE__)).'/../data/curl/'.$credentials['cookiesDir'].'/'.$credentials['cookiesSubDir'].'/';
		$cookieName = $credentials["cookieName"];
		$cookies = $dir.$cookieName.'_cookies.txt';
		
	    $aCookies = array();
	    $aLines = file($cookies);
	    foreach($aLines as $line){
	      if('#'==$line{0})
	        continue;
	      $arr = explode("\t", $line);
	      if(isset($arr[5]) && isset($arr[6]))
	        $aCookies[$arr[5]] = str_replace("\n", "", $arr[6]);
	    }
	   	return $aCookies;
	}
	/**
	 * 
	 * Get the HTML after executing Java Script
	 */
	private function getHiddenParamsAfterJs($credentials){
		$hiddenParams = array();
		
		$loginUrl = 'https://affiliate-program.amazon.co.uk/';
		$this->_client = new Oara_Curl_Access($loginUrl, array(), $credentials);
        
		$cookies = self::readCookies($credentials);
		$cookiesString = "";
		$cookiesNumber = count($cookies);
		$i = 0;
		foreach ($cookies as $cookieName => $cookieValue){
			$cookiesString .= $cookieName."=".$cookieValue;
			if ($i != (count($cookies) - 1)){
				$cookiesString .= "&";
			}
			$i++;
		}
		//AffJet's way to call the JAR FILE, if you are a PHP-OARA user you need to use the other methong, calling java directly
		if (isset($credentials["httpLogin"])){
			$amazonServiceHttpLogin = $credentials["httpLogin"];
			$amazonJavaServer = $credentials["javaServer"];
			$amazonServiceAuthToken = $credentials["authToken"];
			$amazonServiceParseUrl = "https://affiliate-program.amazon.com/";
			
			$amazonServiceUrl = "$amazonJavaServer?auth=$amazonServiceAuthToken&url=$amazonServiceParseUrl&cookie=%22$cookiesString%22";
			$curlSession = curl_init($amazonServiceUrl);
			curl_setopt_array($curlSession, array(
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; U; Linux i686; es-CL; rv:1.9.2.17) Gecko/20110422 Ubuntu/10.10 (maverick) Firefox/3.6.17",
				CURLOPT_FAILONERROR => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER =>false,
				CURLOPT_USERPWD => $amazonServiceHttpLogin
			));
			$htmlAfterJs = curl_exec($curlSession);		
			curl_close($curlSession);
			
			$hiddenParamList = explode("\n", $htmlAfterJs);
			foreach ($hiddenParamList as $hiddenParam){
				$characterNumber = strpos($hiddenParam,":");
				$hiddenName = substr($hiddenParam, 0 , $characterNumber);
				$hiddenValue = substr($hiddenParam, $characterNumber + 1);
				$hiddenParams[$hiddenName] = $hiddenValue;
			}
			
			
		} else {
			$descriptorspec = array(
					            0 => array('pipe', 'r'),
					            1 => array('pipe', 'w'),
					            2 => array('pipe', 'w')
					           );
					           
			$url = "https://affiliate-program.amazon.com/";		           
			$jarPath = realpath(dirname(__FILE__)).'/Amazon/amazon.jar ';
			$metadataReader = proc_open("java -jar $jarPath $url \"$cookiesString\"", $descriptorspec, $pipes, null, null);
			$htmlAfterJs = '';
			$error = '';
			if (is_resource($metadataReader)) {
				
				$stdin = $pipes[0];
				
				$stdout = $pipes[1];
				
				$stderr = $pipes[2];
				
				while (! feof($stdout)) {
					$htmlAfterJs .= fgets($stdout);
				}
				
				while (! feof($stderr)) {
					$error .= fgets($stderr);
				}
				
				fclose($stdin);
				fclose($stdout);
				fclose($stderr);
			}
			
			$exit_code = proc_close($metadataReader);
			
			$dom = new Zend_Dom_Query($htmlAfterJs);
	      	$results = $dom->query('input[type="hidden"]');
	      	$count = count($results);
	      	foreach ($results as $result){
	      		$hiddenName = $result->attributes->getNamedItem("name")->nodeValue;
	      		$hiddenValue = $result->attributes->getNamedItem("value")->nodeValue;
	      		$hiddenParams[$hiddenName] = $hiddenValue;
	      	}
		}
		return $hiddenParams;
	}
}