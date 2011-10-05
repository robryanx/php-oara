<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_As
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_AdSense extends Oara_Network{

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
	 * @param $buy
	 * @return Oara_Network_Buy_Api
	 */
	public function __construct($credentials)
	{

		$user = $credentials['user'];
        $password = $credentials['password'];
        $emailChallenge = null;
        if (isset($credentials['emailChallenge']) && $credentials['emailChallenge'] != null){
        	$emailChallenge = $credentials['emailChallenge'];
        }
		$phoneChallenge = null;
        if (isset($credentials['phoneChallenge']) && $credentials['phoneChallenge'] != null){
        	$phoneChallenge = $credentials['phoneChallenge'];
        }
       

		// /adsense/
		$this->_client = new Oara_Curl_Access('https://www.google.com/adsense/v3/', array(), $credentials);
		
		$urls = array();
		$urls[] = new Oara_Curl_Request("https://www.google.com/adsense/v3/", array());
		$contentList = $this->_client->get($urls);
		
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($contentList[0]);
		$hiddenList = $doc->getElementsByTagName('input');
		$hiddenMap = array();
		$nodeListLength = $hiddenList->length; // this value will also change
		for ($i = 0; $i < $nodeListLength; $i ++)
		{
			$node = $hiddenList->item($i);
			if ($node->getAttribute( 'type' ) == 'hidden'){
				$hiddenMap[$node->getAttribute( 'name' )] = $node->getAttribute( 'value' );
			}
		}
		$valuesLogin = array();
		foreach($hiddenMap as $key => $value) {
			$valuesLogin[] = new Oara_Curl_Parameter($key, $value);
		}
		$valuesLogin[] = new Oara_Curl_Parameter('Email', $user);
		$valuesLogin[] = new Oara_Curl_Parameter('Passwd' , $password);
		$valuesLogin[] = new Oara_Curl_Parameter('signIn' , 'Sign in');

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://accounts.google.com/ServiceLoginAuth', $valuesLogin);
		$content = $this->_client->post($urls);
		
		$dom = new Zend_Dom_Query(current($content));
		$results = $dom->query('#challengeform');
		//We have to provide the challenge
		$count = count($results);
		if ($count > 0){
			
			$challenge = array();
			$challenge[] = new Oara_Curl_Parameter('continue', 'https://www.google.com/adsense/v3/gaiaauth2?destination=/adsense/v3/home');
			$challenge[] = new Oara_Curl_Parameter('jsenabled' , 'true');
			
			$results = $dom->query('#RecoveryEmailChallengeInput');
			$count = count($results);
			if ($count > 0 && $emailChallenge != null ){
				$challenge[] = new Oara_Curl_Parameter('emailAnswer' , $emailChallenge);
				$challenge[] = new Oara_Curl_Parameter('challengetype' , 'RecoveryEmailChallenge');
			} else {
				$results = $dom->query('#PhoneVerificationChallengeInput');
				$count = count($results);
				if ($count > 0 && $phoneChallenge != null){
					$challenge[] = new Oara_Curl_Parameter('phoneNumber' , $phoneChallenge);
					$challenge[] = new Oara_Curl_Parameter('challengetype' , 'PhoneVerificationChallenge');
				}	
			}
			
			$challenge[] = new Oara_Curl_Parameter('answer' , '');
			$challenge[] = new Oara_Curl_Parameter('address' , '');
			$challenge[] = new Oara_Curl_Parameter('submitChallenge' , '');
			
			if ($emailChallenge != null || $phoneChallenge != null){
				$urls = array();
				$urls[] = new Oara_Curl_Request('https://accounts.google.com/LoginVerification?Email='.$user.'&continue=https://www.google.com/adsense/v3/gaiaauth2?destination=/adsense/v3/home&service=adsense', $challenge);
				$content = $this->_client->post($urls);	
			}
			
		}
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/v3/app#home', array());
		$content = $this->_client->get($urls);
		if (preg_match("/signout/", $content[0], $matches) || preg_match("/unsupportedBrowser/", $content[0], $matches)) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array()){
		$merchants = Array();
			
		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Google AdSense";
		$obj['url'] = "www.google.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$totalTransactions = array();
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$overviewArray = array();
		
		$firstDayMonth = new Zend_Date();
		$firstDayMonth->setDay(1);
		$firstDayMonth->setHour("00");
		$firstDayMonth->setMinute("00");
		$firstDayMonth->setSecond("00");
		
		$modeArray = array("AdSense for Content", "AdSense for Search", "AdSense for Feeds", "AdSense for Domains");
		$valuesExport = array();
		$valuesExport[] = new Oara_Curl_Parameter('d', $dStartDate->toString("yyyy/M/d")."-".$dEndDate->toString("yyyy/M/d"));
		$valuesExportReport[] = new Oara_Curl_Parameter('ag', 'date');
		$valuesExport[] = new Oara_Curl_Parameter('oc', 'earnings');
		$valuesExport[] = new Oara_Curl_Parameter('oo', 'descending');
		$valuesExport[] = new Oara_Curl_Parameter('hl', 'en_GB');
		
		$urls = array();
		$valuesExportReport = Oara_Utilities::cloneArray($valuesExport);
		
		$valuesExportReport[] = new Oara_Curl_Parameter('dd', '1YproductY1YAFCYAdSense for Content');
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/v3/gwt/exportCsv?', $valuesExportReport);
		
		$valuesExportReport = Oara_Utilities::cloneArray($valuesExport);
		$valuesExportReport[] = new Oara_Curl_Parameter('dd', '1YproductY1YAFSYAdSense for Search');
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/v3/gwt/exportCsv?', $valuesExportReport);
		
		$valuesExportReport = Oara_Utilities::cloneArray($valuesExport);
		$valuesExportReport[] = new Oara_Curl_Parameter('dd', '1YproductY1YAFFYAdSense for Feeds');
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/v3/gwt/exportCsv?', $valuesExportReport);
		
		$valuesExportReport = Oara_Utilities::cloneArray($valuesExport);
		$valuesExportReport[] = new Oara_Curl_Parameter('dd', '1YproductY1YAFDYAdSense for Domains');
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/v3/gwt/exportCsv?', $valuesExportReport);
		
		
		$content = $this->_client->post($urls);
		for ($i = 0; $i < count($content); $i++){
			$exportData = str_getcsv(@iconv('UTF-16', 'UTF-8',$content[$i]),"\n");
			for ($j = 1; $j < count($exportData); $j++) {
				$overviewExportArray = str_getcsv($exportData[$j],"\t");
					
				$obj = array();
				$obj['merchantId'] = 1;
				$overviewDate = new Zend_Date($overviewExportArray[0],"yyyy-MM-dd");
				$overviewDate->setHour("00");
				$overviewDate->setMinute("00");
				$overviewDate->setSecond("00");
				$obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				 
				$obj['link'] = $modeArray[$i];
				$obj['transaction_number'] = 0;
				$obj['transaction_confirmed_commission'] =  0;
				$obj['transaction_confirmed_value'] =  0;
				$obj['transaction_pending_commission'] =  0;
				$obj['transaction_pending_value'] =  0;
				$obj['transaction_declined_commission'] = 0;
				$obj['transaction_declined_value'] = 0;
					
				$obj['impression_number'] = (int)Oara_Utilities::parseDouble($overviewExportArray[1]);
				$obj['click_number'] =  Oara_Utilities::parseDouble($overviewExportArray[2]);
				if ($firstDayMonth->compare($overviewDate) <= 0){
					$obj['transaction_pending_commission'] = Oara_Utilities::parseDouble($overviewExportArray[6]);
					$obj['transaction_pending_value'] = Oara_Utilities::parseDouble($overviewExportArray[6]);
				} else {
					$obj['transaction_confirmed_commission'] =  Oara_Utilities::parseDouble($overviewExportArray[6]);
					$obj['transaction_confirmed_value'] =  Oara_Utilities::parseDouble($overviewExportArray[6]);
				}
				
				if (Oara_Utilities::checkRegister($obj)){
					$overviewArray[] = $obj;
				}
				
			}
			
		}
		unset ($urls);
		return $overviewArray;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
		$paymentHistory = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/v3/reports-payment?csv=true&historical=false&reportRange=ALL_TIME', array());
		$content = $this->_client->get($urls);
		$exportData = str_getcsv(@iconv('UTF-16', 'UTF-8',$content[0]),"\n");
		$num = count($exportData);
		for ($j = 1; $j < $num; $j++) {
			$paymentExportArray = str_getcsv($exportData[$j],"\t");
			if ($paymentExportArray[1] == "Payment issued"){
				$obj = array();
				$date = new Zend_Date($paymentExportArray[0], "MM/dd/yy");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = $date->toString("yyMMdd");
				$obj['method'] = 'BACS';
				$obj['value'] = abs(Oara_Utilities::parseDouble($paymentExportArray[2]));
				$paymentHistory[] = $obj;
			}
		}
		return $paymentHistory;
	}
}
